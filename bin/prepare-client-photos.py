#!/usr/bin/env python3
"""Orient, strip EXIF, and write public derivatives for the client photo import."""

import hashlib
import json
from pathlib import Path

from PIL import Image, ImageOps

ROOT = Path(__file__).resolve().parents[1] / ".cache" / "client-photos"
OUT = Path(__file__).resolve().parents[1] / "wordpress" / "wp-content" / "mytaxi-photo-import"
LONG = 1600
CARD = 800


def load(rel):
    image = Image.open(ROOT / rel)
    image = ImageOps.exif_transpose(image).convert("RGB")
    return image


# Top of a 3:2 window on the oriented 3000×4000 frame.
# Chosen so the vehicle stays in the landscape hero and cards.
CROP_Y = {
    "varna-airport-transfer-car-day": 1180,
    "golden-sands-taxi-international": 1480,
    "golden-sands-taxi-admiral": 1550,
    "golden-sands-taxi-karting": 1500,
    "golden-sands-taxi-seaside-dusk": 1550,
    "golden-sands-taxi-international-side": 1500,
    "albena-taxi-arched-building": 1450,
    "albena-taxi-seaside": 1620,
    "albena-taxi-flamingo-grand": 1500,
    "albena-taxi-glass-hotel-dusk": 1450,
    "albena-taxi-street-dusk": 1550,
    "taxi-rear-seat-interior": 1100,
    "varna-airport-transfer-car-night": 1500,
    "varna-airport-transfer-car-night-rear": 1450,
    "varna-airport-transfer-car-night-side": 1400,
    "varna-airport-transfer-car-with-person": 1400,
    "taxi-fortress-gate-coast": 1400,
    "taxi-fortress-gate-coast-front": 1450,
    "kranevo-taxi-veramar-beach": 1740,
    "transfer-up-to-5-passengers": 1200,
    "sofia-transfer-up-to-5-passengers": 1260,
}


def landscape(image, top):
    width, height = image.size
    crop_height = int(round(width * 2 / 3))
    if crop_height >= height:
        return image
    top = max(0, min(int(top), height - crop_height))
    return image.crop((0, top, width, top + crop_height))


def shrink(image, long_side):
    copy = image.copy()
    copy.thumbnail((long_side, long_side), Image.Resampling.LANCZOS)
    return copy


def save_pair(image, stem, quality):
    jpeg = OUT / f"{stem}.jpg"
    webp = OUT / f"{stem}.webp"
    image.save(jpeg, format="JPEG", quality=quality, optimize=True, progressive=True)
    image.save(webp, format="WEBP", quality=quality, method=6)
    return jpeg, webp


def main():
    OUT.mkdir(parents=True, exist_ok=True)
    items = [
        {
            "source": "airport/20260613_190846.jpg",
            "stem": "varna-airport-transfer-car-day",
            "alt": "Taxi vehicle outside Varna Airport terminal",
            "featured": ["front", "varna-airport-transfers", "page:airport-transfers", "page:routes"],
        },
        {
            "source": "goldensands/20260711_183353.jpg",
            "stem": "golden-sands-taxi-international",
            "alt": "Taxi vehicle outside International Hotel in Golden Sands",
            "featured": ["taxi-golden-sands", "varna-airport-to-golden-sands", "page:local-taxi"],
        },
        {
            "source": "goldensands/20260714_171217.jpg",
            "stem": "golden-sands-taxi-admiral",
            "alt": "Taxi vehicle outside Hotel Admiral",
            "gallery": {"taxi-golden-sands": 10},
            "vehicle_4": ["varna-airport-transfers"],
        },
        {
            "source": "goldensands/20260721_160016.jpg",
            "stem": "golden-sands-taxi-karting",
            "alt": "Taxi vehicle at the Varna Karting Track entrance",
            "gallery": {"taxi-golden-sands": 20},
        },
        {
            "source": "goldensands/20260904_193649.jpg",
            "stem": "golden-sands-taxi-seaside-dusk",
            "alt": "Taxi vehicle beside a seaside restaurant at dusk",
            "gallery": {"taxi-golden-sands": 30},
        },
        {
            "source": "goldensands/20260711_183343.jpg",
            "stem": "golden-sands-taxi-international-side",
            "alt": "Taxi vehicle outside International Hotel, seen from the side",
            "gallery": {"taxi-golden-sands": 40},
        },
        {
            "source": "albena/20200713_151200.jpg",
            "stem": "albena-taxi-seaside",
            "alt": "Yellow taxi on a tree-lined street beside the sea",
            "featured": ["taxi-albena"],
        },
        {
            "source": "albena/20260620_154726.jpg",
            "stem": "albena-taxi-arched-building",
            "alt": "Taxi vehicle in front of a white arched building with the Nona sign",
            "featured": ["varna-airport-to-albena"],
        },
        {
            "source": "albena/20260613_165435.jpg",
            "stem": "albena-taxi-flamingo-grand",
            "alt": "Taxi vehicle outside Flamingo Grand hotel",
            "gallery": {"taxi-albena": 10},
        },
        {
            "source": "albena/20260620_050251.jpg",
            "stem": "albena-taxi-glass-hotel-dusk",
            "alt": "Taxi vehicle outside a glass-fronted hotel at dusk",
            "gallery": {"taxi-albena": 20},
        },
        {
            "source": "albena/20260708_205135.jpg",
            "stem": "albena-taxi-street-dusk",
            "alt": "Taxi vehicle on a residential street at dusk",
            "gallery": {"taxi-albena": 30},
        },
        {
            "source": "airport/20260908_151530.jpg",
            "stem": "taxi-rear-seat-interior",
            "alt": "Rear passenger seats inside the taxi vehicle",
            "gallery": {"taxi-albena": 40, "varna-airport-transfers": 10},
        },
        {
            "source": "airport/20250814_035943.jpg",
            "stem": "varna-airport-transfer-car-night",
            "alt": "Taxi vehicle outside Varna Airport terminal at night",
            "gallery": {"varna-airport-transfers": 20},
        },
        {
            "source": "airport/20250814_035949.jpg",
            "stem": "varna-airport-transfer-car-night-rear",
            "alt": "Taxi vehicle outside Varna Airport terminal at night, rear view",
            "gallery": {"varna-airport-transfers": 30},
        },
        {
            "source": "airport/20250814_035958.jpg",
            "stem": "varna-airport-transfer-car-night-side",
            "alt": "Taxi vehicle beside Varna Airport terminal at night",
            "gallery": {"varna-airport-transfers": 40},
        },
        {
            "source": "airport/20250815_145945.jpg",
            "stem": "varna-airport-transfer-car-with-person",
            "alt": "Taxi vehicle outside Varna Airport terminal with a person at the open boot",
            "review": "person",
            "caption": "Awaiting confirmation before publication. A person is visible beside the vehicle.",
        },
        {
            "source": "goldensands/20260802_163551.jpg",
            "stem": "taxi-fortress-gate-coast",
            "alt": "Taxi vehicle beside a stone fortress gate on the coast",
            "review": "location",
            "caption": "Location is not confirmed. Not published.",
        },
        {
            "source": "kranevo/20250822_140742.jpg",
            "stem": "kranevo-taxi-veramar-beach",
            "alt": "Taxi vehicle outside the Veramar Beach building",
            "featured": ["taxi-kranevo"],
            "revision": "3x2-3",
        },
        {
            "source": "transfers/20250618_193248.jpg",
            "stem": "transfer-up-to-5-passengers",
            "alt": "Yellow taxi vehicle for a transfer of up to 5 passengers",
            "vehicle": ["varna-airport-transfers"],
        },
        {
            "source": "sofia/20250830_185406.jpg",
            "stem": "sofia-transfer-up-to-5-passengers",
            "alt": "Yellow taxi vehicle for a Sofia Airport transfer of up to 5 passengers",
            "vehicle": ["sofia-airport-transfers"],
        },
        {
            "source": "albena/20260802_163632.jpg",
            "stem": "taxi-fortress-gate-coast-front",
            "alt": "Taxi vehicle beside a stone fortress gate on the coast",
            "review": "location",
            "caption": "Location is not confirmed. A person is also visible. Not published.",
        },
    ]

    manifest = []
    for item in items:
        source = ROOT / item["source"]
        digest = hashlib.sha256(source.read_bytes()).hexdigest()
        image = landscape(load(item["source"]), CROP_Y[item["stem"]])
        large = shrink(image, LONG)
        small = shrink(image, CARD)
        quality = 82
        jpeg, webp = save_pair(large, item["stem"], quality)
        while jpeg.stat().st_size > 360 * 1024 and quality > 74:
            quality -= 4
            jpeg, webp = save_pair(large, item["stem"], quality)
        card_jpeg, card_webp = save_pair(small, item["stem"] + "-card", min(quality, 80))
        manifest.append(
            {
                "source": item["source"],
                "sha256": digest,
                "file": jpeg.name,
                "webp": webp.name,
                "card_webp": card_webp.name,
                "width": large.size[0],
                "height": large.size[1],
                "card_width": small.size[0],
                "alt": item["alt"],
                "caption": item.get("caption", ""),
                "review": item.get("review", ""),
                "featured": item.get("featured", []),
                "gallery": item.get("gallery", {}),
                "vehicle": item.get("vehicle", []),
                "vehicle_4": item.get("vehicle_4", []),
                "jpeg_bytes": jpeg.stat().st_size,
                "webp_bytes": webp.stat().st_size,
                "card_jpeg_bytes": card_jpeg.stat().st_size,
                "card_webp_bytes": card_webp.stat().st_size,
                "quality": quality,
                "revision": item.get("revision", "3x2-1"),
            }
        )
        print(
            f"{item['stem']:42} {large.size[0]}x{large.size[1]:<4} "
            f"jpg {jpeg.stat().st_size // 1024}KB webp {webp.stat().st_size // 1024}KB "
            f"card {card_webp.stat().st_size // 1024}KB q{quality}"
        )

    (OUT / "manifest.json").write_text(json.dumps(manifest, indent=2), encoding="utf-8")
    print("wrote", len(manifest), "derivatives")


if __name__ == "__main__":
    main()
