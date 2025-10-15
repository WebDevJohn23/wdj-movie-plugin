# WDJ Movie Plugin

**Author:** WebDevJohn  
**License:** GPL-2.0+  
**Status:** Active

## Overview
Displays movies currently playing at selected Regal theatres. Pulls poster URLs and basic details from regmovies.com, stores them in WordPress, and renders via shortcode.

## Features
- Scrapes theatre pages once per refresh
- Stores unique movies with a list of theatres
- Shortcode front end
- Admin refresh page

## Installation
1. Upload to `/wp-content/plugins/wdj-movie-plugin/`.
2. Activate in WordPress.
3. Go to **Settings → WDJ Movie Plugin** and click **Refresh**.

## Usage
Place this on a page or post:
```
[wdj_movies]
```
Optional status filter:
```
[wdj_movies status="0"]  // 0 in theatres, 1 watched, 2 not interested
```

## Refresh
- Admin page triggers a scrape of configured theatre URLs.
- Posters are not downloaded. Stored as direct CDN URLs.

## Notes
- If a date is missing on the listing, `dateStarted` stays null and is hidden on output.
- If a theatre page is blocked by a bot wall, zero rows will be added for that theatre.

## Roadmap
- Hourly cron refresh toggle
- Duplication guard improvements
- Per-theatre counts on refresh status
- Optional split into `wp_wdj_movies_data` and a theatres mapping table

## License
GPL-2.0+. See `LICENSE`.