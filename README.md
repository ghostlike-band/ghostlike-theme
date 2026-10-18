# GHOSTLIKE – WordPress-Theme

Onepager-Theme für [ghostlike.de](https://ghostlike.de) im CI „GL-27 / Ära I“.

## Inhalte pflegen
- **Hero-Slides**: Bild oder Video (MP4-Loop), Headline, Button
- **Releases**: Cover, Tracklist (`Titel | single | Video-Link | Cover-URL`), Streaming-Links
- **Tourdaten**: Datum, Stadt, Venue, Status, Ticket-Link
- **Beiträge**: News/Blog
- **Design → Customizer → GHOSTLIKE**: Bio, Mitglieder, Social-Links, Kontakt-E-Mails, Social-Bilder
- **Design → GHOSTLIKE**: Version, Update-Check, Demo-Inhalte

## Updates veröffentlichen
1. `Version:` in `style.css` hochzählen (z. B. `0.1.1`)
2. Committen, Tag setzen und pushen: `git tag v0.1.1 && git push --tags`
3. Die GitHub Action baut `ghostlike.zip` und hängt sie ans Release
4. In WordPress unter **Dashboard → Aktualisierungen** auf „Aktualisieren“ klicken

Das Repo wird über die Konstante `GL_GITHUB_REPO` in `functions.php` festgelegt (oder in `wp-config.php` überschreiben).

## Lokal bauen
`bin/build-zip.sh` → `build/ghostlike.zip`
