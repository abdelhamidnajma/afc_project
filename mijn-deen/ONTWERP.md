# Mijn Deen — ontwerpdocument (werktitel)

> De naam "Mijn Deen" is een tijdelijke placeholder. **Alle religieuze inhoud (verzen, vertalingen, betekenissen, lesteksten, bronnen) is placeholder en moet door een deskundige worden gecontroleerd.** Er zijn geen citaten, vertalingen of hadith verzonnen.

Bestanden:
- `prototype.html` — klikbaar prototype (licht/donker, klein scherm 320 px, alle 7 schermen en toestanden)
- `tokens.json` — design tokens (kleur, typografie, spacing, radii, schaduw) voor beide thema's

---

## Fase 1 — Probleem en strategie

**Probleemstelling.** Mensen die de islam (opnieuw) leren kennen, vinden veel informatie die versnipperd, te diep of veroordelend is. Bestaande apps zijn vaak druk, gericht op prestaties (streaks, ranglijsten) of gaan uit van voorkennis. *Hoe helpen we iemand om in kleine, rustige stappen de basis te leren en in praktijk te brengen, zonder druk of schuldgevoel?*

**Persona's**
| Persona | Situatie | Behoefte | Angst |
|---|---|---|---|
| Sara, 27, net bekeerd | Kent weinig Arabisch, vaak alleen | Duidelijke uitleg, uitspraak, "wat doe ik nu?" | Fouten maken, beoordeeld worden |
| Karim, 41, frist op | Opgegroeid moslim, basis weggezakt | Snel iets opzoeken, gebedstijden | Zich schamen voor wat hij "zou moeten weten" |
| Ineke, 63, stapsgewijze leerder | Weinig app-ervaring, slechter zicht | Grote tekst, weinig keuzes per scherm | Verdwalen in de app |

**Benchmark (alleen patronen).** Leerapps zoals Hidaya: modules met korte lessen, gebedstijden, Koran-lezer, qibla, dhikr. Gebedsapps: veel informatie tegelijk, vroege toestemmingsvragen, notificatie-druk. Taalapps: gamification met streaks. *Overgenomen patronen:* korte modules, "ga verder"-kaart, per-vers weergave. *Bewust vermeden:* streaks, punten, ranglijsten, toestemmingsvragen bij eerste start, overvolle startschermen.

**Ontwerpprincipes**
1. **Eén ding tegelijk** — elk scherm heeft één hoofdactie.
2. **Geen druk** — geen streaks, geen "je hebt gemist", geen rode badges.
3. **Uitnodigend, niet oordelend** — "Ga verder wanneer je wilt" in plaats van "Je loopt achter".
4. **Context vóór toestemming** — locatie/meldingen pas vragen wanneer een functie het nodig heeft, met uitleg.
5. **Leesbaarheid eerst** — grote basismaat, sterk contrast, dynamische tekst, 48 px tikdoelen.

## Fase 2 — Designsysteem

**Kleur.** Diepgroen `#1E5A44` (primair), goud als accent (`#8A6420` voor tekst/iconen in licht thema — het lichtere goud `#D9B977` haalt geen AA op crème en wordt alleen in donker thema of decoratief gebruikt), crème `#FBF8F2` achtergrond, inkt `#1C1F1D`. Donker thema: zachtgroen `#7CC4A4` op `#0E1512`.

Contrast (WCAG 2.2 AA):
| Combinatie | Ratio | |
|---|---|---|
| text op bg (licht) | ≈16:1 | AAA |
| text-2 op bg (licht) | ≈8:1 | AAA |
| primary op bg / wit op primary | ≈7.5:1 | AAA |
| accent op accent-soft (licht) | ≈4.7:1 | AA |
| text op bg (donker) | ≈16:1 | AAA |
| primary op bg (donker) | ≈9:1 | AAA |

**Typografie.** Atkinson Hyperlegible (ontworpen voor slechtziendheid) voor Nederlands; Amiri/Noto Naskh Arabic voor Koran en termen, altijd `dir="rtl"` en `lang="ar"`, regelhoogte 2.0 voor harakat. Basis 17 px, alle maten in tokens zodat iOS Dynamic Type / Android font scale ze kan schalen (iOS: `UIFontMetrics`, Android: `sp`).

**Componenten**
- **Knoppen:** primair (gevuld groen), secundair (groene rand), rustig (alleen tekst). Altijd ≥48 px hoog, pill-vorm, zichtbare focusring in goud.
- **Kaart:** wit/oppervlak, 16 px radius, 1 px rand, zachte schaduw. Variant *hero* in zacht groen met spaarzaam geometrisch achtpuntig motief (12% dekking).
- **Tabbalk:** 4 tabs (Vandaag, Leren, Koran, Profiel), lijniconen + altijd een label, actieve tab groen en vet + `aria-current`.
- **Voortgang:** dunne gouden balk of stippen "les 2 van 5". Geen percentages-als-score, geen vergelijking met anderen.
- **Gebedskaart:** volgend gebed + resterende tijd + 5 tijden in rij + rond afvinkknop; afvinken is omkeerbaar en geeft alleen een vriendelijke bevestiging.

**Iconen.** Eén lijnstijl, 1.8 px stroke, 24 px grid, afgeronde uiteinden.

## Fase 3 — Schermen (zie prototype)

1. **Vandaag** — begroeting, datum + islamitische datum, gebedskaart (dominant), "Jouw volgende stap" (één les), dhikr/qibla als kleine snelknoppen, ayah van de dag onderaan. Hiërarchie: gebed → leren → hulpmiddelen → inspiratie.
2. **Leren** — "Hier was je gebleven"-kaart bovenaan, daaronder alle modules (vrij te kiezen) met aantal lessen en rustige voortgang.
3. **Lesdetail** — één onderwerp, korte tekst, Arabische term-kaart (Arabisch, uitspraak, betekenis), genummerde stappen, bronnen in uitklapblok, "Les afronden", vorige/volgende.
   *Toestanden:* niet gestart (knop "Begin les") → bezig (positie bewaard) → afgerond (✓, knop wordt secundair) → vorige/volgende altijd beschikbaar, nooit vergrendeld.
4. **Koran** — soera-kiezer (bottom sheet met zoeken), A−/A+, per vers: nummer, Arabisch RTL, transliteratie, vertaling, bladwijzer; audiobalk onderaan.
5. **Dhikr** — groot rond telvlak (240 px), Arabisch + transliteratie + betekenis, ongedaan maken, reset met bevestiging, optioneel doel.
   *Toestanden:* leeg ("Tik om te tellen") → tellen → doel bereikt (zachte trilling, "je mag gewoon doorgaan") → reset-bevestiging.
6. **Qibla** — *toestanden:* nog niet gevraagd (uitleg + "Locatie gebruiken" / "Stad zelf kiezen") → geweigerd (stad kiezen, geen verwijt) → kalibreren (8-beweging) → onnauwkeurig (gouden chip "Mogelijk onnauwkeurig" + kalibreer-link) → nauwkeurig.
7. **Profiel** — persoonlijke voortgang zonder vergelijking, instellingen met uitleg in gewone taal (taal, locatie, rekenmethode, herinneringen, thema, privacy).

**Foutstromen (qibla & gebedstijden)**
| Situatie | Melding | Uitweg |
|---|---|---|
| Geen locatie | "We weten je locatie niet. Kies een stad om tijden te zien." | Stad kiezen |
| Geen kompassensor | "Je telefoon heeft geen kompas. Hier is de richting in graden vanaf het noorden." | Statische kaart + graden |
| Geen internet | "Je bent offline. We tonen de laatst opgeslagen tijden." | Tijden worden 30 dagen vooruit lokaal berekend |

**Lege / laad / succes-toestanden (alle schermen)**
- Laden: skeletkaarten in `surface-2`, geen spinners langer dan 1 s zonder tekst.
- Leeg: Leren zonder voortgang → "Waar wil je beginnen?"; geen bladwijzers → "Tik op ☆ bij een vers om het hier te bewaren."
- Succes: korte toast onderaan, 2,5 s, nooit confetti.
- Fout: wat er mis is + wat je kunt doen, nooit "Fout 403".

**Schrijfstijl:** je-vorm, korte zinnen, B1-niveau, uitnodigend ("wanneer je wilt"), nooit "vergeten", "gemist" of "moet".

## Fase 4 — Prototype

`prototype.html` dekt: tabnavigatie, gebed afvinken (en ongedaan), les openen → afronden → vorige/volgende, Koran lezen + soera-sheet + lettergrootte + bladwijzer, dhikr tellen/ongedaan/doel/reset-bevestiging, qibla met toestemmingsuitleg, weigering en kalibratie. Bedieningspaneel links schakelt licht/donker en klein scherm.

## Fase 5 — Controle (gevonden en verwerkt)

| Prio | Bevinding | Verwerkt |
|---|---|---|
| Hoog | Goud `#D9B977` als tekst op crème = ~1.9:1 | Tekstgoud → `#8A6420` (AA) |
| Hoog | Tabbladen alleen met iconen onduidelijk voor beginners | Altijd label zichtbaar |
| Hoog | Afvinken onomkeerbaar = angst voor fouten | Toggle + toast "ongedaan gemaakt" |
| Mid | Arabische tekst te krap met harakat | regelhoogte 2.0, min. 22 px |
| Mid | 320 px: tabellabels gebedstijden te krap | 5 kolommen 13 px, getest in klein-scherm-modus |
| Mid | iOS vs Android | iOS: tabbalk met home-indicatormarge, sheet met grabber; Android: Material-backgesture, systeem-terugknop sluit sheet; beide: 48 dp/44 pt+ tikdoelen |
| Laag | Reset zonder bevestiging | Bevestigingssheet toegevoegd |
| Laag | Kleur als enige statusdrager in voortgang | Tekst "2 van 5 / Afgerond ✓" naast balk |

Openstaand: echte gebruikerstest met de 3 persona's (5 deelnemers, taken: gebed afvinken, les vinden en afronden, qibla zonder locatietoestemming).

## Fase 6 — Ontwerpkeuzes (rationale) en overdracht

- **Gebed bovenaan, leren eronder:** het gebed is dagelijks en tijdgebonden; leren is flexibel.
- **Geen streaks:** streaks veroorzaken schuld bij een gemiste dag — precies wat deze doelgroep afschrikt.
- **Atkinson Hyperlegible:** hoogste leesbaarheid voor oudere en slechtziende gebruikers, gratis licentie.
- **Groen + goud spaarzaam:** herkenbaar islamitisch zonder clichés; het geometrische motief verschijnt alleen in hero-kaarten.
- **Toestemming in context:** vertrouwen opbouwen; app werkt volledig met handmatig gekozen stad.

**Overdracht voor ontwikkelaars**
- Tokens: `tokens.json` → Style Dictionary naar Swift/Kotlin/CSS.
- Tikdoelen ≥48 px, focusvolgorde = leesvolgorde, alle iconknoppen hebben `aria-label`/`contentDescription`.
- Arabisch altijd in eigen element met `lang="ar"` en RTL; nooit inline mixen in Latijnse regels zonder bidi-isolatie.
- Gebedstijden lokaal berekenen (bijv. adhan-bibliotheek), rekenmethode instelbaar, standaard op regio.
- Inhoud komt uit een CMS met veld `reviewStatus`; alleen `approved` wordt in productie getoond.
