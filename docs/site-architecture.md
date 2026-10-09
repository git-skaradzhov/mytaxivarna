# Архитектура

## Адреси

| Адрес | Запис |
| --- | --- |
| `/` | Начална страница |
| `/taxi-golden-sands/` | Локално такси |
| `/taxi-albena/` | Локално такси |
| `/taxi-kranevo/` | Локално такси |
| `/varna-airport-transfers/` | Airport transfers |
| `/sofia-airport-transfers/` | Airport transfers |
| `/varna-airport-to-golden-sands/` | Маршрут към Varna Airport Transfers |
| `/varna-airport-to-albena/` | Маршрут към Varna Airport Transfers |
| `/varna-airport-to-kranevo/` | Маршрут към Varna Airport Transfers |
| `/varna-airport-to-balchik/` | Маршрут към Varna Airport Transfers |
| `/sofia-airport-to-sofia/` | Маршрут към Sofia Airport Transfers |
| `/contact/` | Контакти по услуги, без обща форма |

Няма публичен архив на услугите или маршрутите.

## Намерение при търсене

| Страница | Намерение |
| --- | --- |
| Taxi Albena | човек е в Албена и търси локално такси |
| Varna Airport → Albena | човек планира трансфер от летище Варна |
| Taxi Golden Sands | човек е в Golden Sands и търси локално такси |
| Varna Airport → Golden Sands | трансфер от летище Варна до Golden Sands |
| Varna Airport Transfers | обща услуга за летище Варна |
| Sofia Airport Transfers | отделна услуга за летище София |

Локалното такси и летищният трансфер не споделят телефон.

## SEO

Един H1. Meta title и description са полета на записа. Canonical е стандартният на WordPress към адреса на записа. Sitemap е вграденият на WordPress, без потребители. Local и staging са noindex. Open Graph и JSON-LD се печатат от MyTaxi Core, докато няма SEO плъгин.

Schema: Organization за сайта, TaxiService за услуга, Service за маршрут, Offer само при потвърдена положителна цена, FAQ само за видимите въпроси, BreadcrumbList. Услугите не са отделни юридически лица.

## Google Business

Профили не се създават и не се сливат. URL адресите са празни, докато не бъдат дадени.

| Профил | Услуга | Телефон за потвърждение | Нова страница |
| --- | --- | --- | --- |
| Golden Sands | Taxi Golden Sands | +359884811045 | `/taxi-golden-sands/` |
| Albena | Taxi Albena | +359896817384 | `/taxi-albena/` |
| Kranevo | Taxi Kranevo | +359896816334 | `/taxi-kranevo/` |
| Varna Airport Transfers | Varna Airport Transfers | +359888850884 | `/varna-airport-transfers/` |
| Sofia Airport Transfers | Sofia Airport Transfers | липсва | `/sofia-airport-transfers/` |
