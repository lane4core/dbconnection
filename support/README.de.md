# Erklärung des Shellskript-Patterns

Das angegebene Pattern im Shellskript lautet:
```
pattern="^(feature|fix|hotfix)\/\d{6,7}_[a-zA-Z0-9_-]+|:[0-9a-f]{7,40}$"
```

Dieser reguläre Ausdruck definiert folgende gültige und ungültige Formate:

---

## **Gültige Formate** (Erfüllen das Pattern)
1. **Feature-, Fix- oder Hotfix-Branches**:
   - **Format**: `feature/123456_something` oder `fix/1234567_something_else`
   - **Beschreibung**:
      - Beginnt mit `feature/`, `fix/` oder `hotfix/`.
      - Darauf folgt eine Ziffernfolge aus 6 oder 7 Ziffern (`\d{6,7}`).
      - Danach ein Unterstrich (`_`) und mindestens ein Zeichen aus `[a-zA-Z0-9_-]`.

   **Beispiele**:
   - `feature/123456_example-feature`
   - `fix/1234567_bug-fix`
   - `hotfix/987654_update`

2. **Git-Commit-Hashes**:
   - **Format**: `:abc1234`, `:abcdef1234567890` oder `:123456789abcdef...`
   - **Beschreibung**:
      - Beginnt mit einem Doppelpunkt (`:`).
      - Darauf folgt eine Hexadezimalfolge von 7 bis 40 Zeichen (`[0-9a-f]{7,40}`).

   **Beispiele**:
   - `:abc1234`
   - `:abcdef1234567890`
   - `:123456789abcdefabcdefabcdefabcdefabcdef`

---

## **Ungültige Formate** (Erfüllen das Pattern nicht)
1. **Ungültige Branchnamen**:
   - Beginnt nicht mit `feature/`, `fix/` oder `hotfix/`.
   - Enthält weniger als 6 Ziffern.
   - Fehlt ein `_` nach den Ziffern.

   **Beispiele**:
   - `update/123456_example` (falsches Präfix)
   - `fix/1234_example` (zu wenig Ziffern)
   - `fix/123456example` (kein `_` zwischen Ziffern und Name)

2. **Ungültige Commit-Hashes**:
   - Fehlt das Doppelpunkt-Präfix (`:`).
   - Hexadezimalfolgen kürzer als 7 Zeichen oder länger als 40 Zeichen.
   - Enthält ungültige Zeichen.

   **Beispiele**:
   - `abc1234` (fehlt das `:`)
   - `:1234` (zu kurz)
   - `:123456789ghijklmnop` (ungültige Zeichen)

---

## **Zusammenfassung**
Das Pattern funktioniert für:
- Branchnamen mit festgelegtem Präfix und strukturierter Bezeichnung.
- Git-Commit-Hashes im Format `:[0-9a-f]{7,40}`.

Falls du unsicher bist, ob ein bestimmter Input gültig ist, kannst du ihn testen oder um Hilfe bitten!
