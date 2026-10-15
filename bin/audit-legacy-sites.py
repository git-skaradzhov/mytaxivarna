#!/usr/bin/env python3
"""Read-only, bounded legacy URL inventory. No redirects are activated.

python3 bin/audit-legacy-sites.py --output docs/legacy-url-inventory.csv --max-pages 30
Search Console, analytics and backlink exports must supplement this crawl.
"""
import argparse
import html as html_module
import collections
import csv
import re
import time
import urllib.error
import urllib.parse
import urllib.request
import urllib.robotparser
import xml.etree.ElementTree as ET
from html.parser import HTMLParser

HOSTS = ('mytaxivarna.com', 'taxialbena.com', 'taxikranevo.com', 'airporttaxitransfersvarna.com')

class Page(HTMLParser):
    def __init__(self):
        super().__init__(); self.links = []; self.title = ''; self.canonical = ''; self.in_title = False; self.in_head = False
    def handle_starttag(self, tag, attrs):
        d = dict(attrs)
        if tag == 'a' and d.get('href'): self.links.append(d['href'])
        if tag == 'link' and d.get('rel') == 'canonical': self.canonical = d.get('href', '')
        if tag == 'head': self.in_head = True
        if tag == 'title' and self.in_head: self.in_title = True
    def handle_endtag(self, tag):
        if tag == 'title': self.in_title = False
        if tag == 'head': self.in_head = False
    def handle_data(self, value):
        if self.in_title: self.title += value

def fetch(url):
    request = urllib.request.Request(url, headers={'User-Agent': 'MyTaxiMigrationAudit/1.0'})
    try:
        with urllib.request.urlopen(request, timeout=12) as r:
            return r.status, r.url, r.headers.get('Content-Type', ''), r.read(2_000_000).decode('utf-8', 'replace')
    except urllib.error.HTTPError as e:
        return e.code, e.url, '', ''
    except Exception as e:
        return 'network_error', url, '', type(e).__name__

def normalize(url, host):
    p = urllib.parse.urlsplit(url)
    if p.hostname not in (host, 'www.' + host): return None
    if p.scheme not in ('http', 'https'): return None
    if re.search(r'\.(jpg|jpeg|webp|png|gif|svg|pdf|zip|css|js|xml)$', p.path, re.I): return None
    if p.path.startswith(('/wp-admin', '/wp-json', '/wp-login')): return None
    if p.query and not re.fullmatch(r'p=\d+', p.query): return None
    return urllib.parse.urlunsplit(('https', host, p.path or '/', p.query, ''))

def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output', required=True)
    parser.add_argument('--max-pages', type=int, default=30)
    args = parser.parse_args()
    with open('docs/redirect-map.csv', newline='') as f:
        legacy_rows = list(csv.DictReader(f))
        mappings = {r['old_url'].rstrip('/'): r['new_url'] for r in legacy_rows if r['new_url']}
    rows = []
    for host in HOSTS:
        base = 'https://' + host
        queue = collections.deque([base + '/'])
        queue.extend(r['old_url'] for r in legacy_rows if urllib.parse.urlsplit(r['old_url']).hostname == host)
        robots = urllib.robotparser.RobotFileParser()
        status, _, _, body = fetch(base + '/robots.txt')
        if status == 200: robots.parse(body.splitlines())
        sitemaps = re.findall(r'^Sitemap:\s*(\S+)', body, re.M | re.I) if status == 200 else []
        sitemaps += [base + '/sitemap.xml', base + '/wp-sitemap.xml']
        seen_maps = set()
        for sm in sitemaps:
            if sm in seen_maps or urllib.parse.urlsplit(sm).hostname not in (host, 'www.' + host): continue
            seen_maps.add(sm)
            sc, _, _, xml = fetch(sm)
            if sc != 200: continue
            try:
                root = ET.fromstring(xml)
                locs = [e.text for e in root.iter() if e.tag.endswith('}loc') or e.tag == 'loc']
                if root.tag.endswith('sitemapindex'):
                    sitemaps.extend(locs[:10])
                else:
                    queue.extend(url for url in locs if url)
            except ET.ParseError: pass
            if len(seen_maps) >= 12: break
        seen = set()
        while queue and len(seen) < args.max_pages:
            url = normalize(queue.popleft(), host)
            if not url or url in seen: continue
            seen.add(url)
            if status == 200 and not robots.can_fetch('MyTaxiMigrationAudit', url):
                rows.append([url, 'robots_disallowed', '', '', '', mappings.get(url.rstrip('/'), ''), 'manual_review'])
                continue
            sc, final, mime, html = fetch(url)
            page = Page()
            if 'html' in mime:
                page.feed(html)
                queue.extend(urllib.parse.urljoin(final, link) for link in page.links)
            title_match = re.search(r'<title[^>]*>(.*?)</title>', html, re.S | re.I)
            title = html_module.unescape(title_match.group(1)).strip() if title_match else ''
            target = mappings.get(url.rstrip('/'), '')
            if url == 'https://mytaxivarna.com/': target = 'https://mytaxivarna.com/'
            decision = 'mapped_needs_review' if target else 'decision_required'
            rows.append([url, sc, final, title, page.canonical, target, decision])
            print(host, sc, url, flush=True)
            time.sleep(.15)
    with open(args.output, 'w', newline='') as f:
        writer = csv.writer(f, lineterminator='\n')
        writer.writerow(['old_url','http_status','final_url','title','old_canonical','proposed_new_url','decision'])
        writer.writerows(rows)
    print('Inventory:', len(rows), 'URLs. A bounded crawl is not a complete historical inventory.')

if __name__ == '__main__': main()
