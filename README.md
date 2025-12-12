# WDJ Movie Plugin

**Author:** WebDevJohn  
**License:** GPL-2.0+  
**Status:** Active – initial development

---

## Project Status

This repository represents a point-in-time build created for demonstration, experimentation, or portfolio review.

- Development state may vary over time
- Some dependencies or external integrations may require updates to run locally
- Code is provided for review of structure, patterns, and problem-solving approach
- Not intended as a drop-in production deliverable

This project is best evaluated by reviewing the codebase and commit history rather than expecting long-term maintenance or turnkey execution.

---

## Overview

WDJ Movie Plugin displays movies currently playing at selected Regal theatres.  
It pulls movie titles and basic details from **regmovies.com**, stores them in a custom WordPress table,  
and displays results on the front end using a shortcode.

This plugin is designed for clarity and extensibility, making it a simple example of real-world data integration inside WordPress.

---

## Features

- Scrapes Regal theatre pages once per admin refresh
- Stores **unique movies** with a list of associated theatres
- Provides `[wdj_movies]` shortcode for front-end display
- Simple admin settings page under **Settings → WDJ Movie Plugin**
- Modular code organized into `includes/` (db, functions, shortcode, settings)

---

## Installation

1. Clone or download this repository into your WordPress `wp-content/plugins/` directory
2. Activate **WDJ Movie Plugin** from the WordPress admin → Plugins page
3. Navigate to **Settings → WDJ Movie Plugin**
4. Click **Refresh Theatre Data** to populate the movie list
5. Use the shortcode `[wdj_movies]` in any post or page

---

## Current Limitations

- Poster URLs and extra movie metadata (release date, synopsis, etc.) are **in progress**
- Theatre list is currently hard-coded
- Only tested with Regal HTML structure as of October 2025

---

## Roadmap

- ✅ Basic theater scraper and shortcode display
- ⏳ Poster and description fetch from individual movie pages
- ⏳ Option to add or remove theaters in admin
- ⏳ Improved front-end layout with responsive grid and sticky section headers

---

## Usage

Place this on a page or post:

```
[wdj_movies]
```

Displays a grid of movies currently playing across your selected theaters.

---

## Contributing

Pull requests and issue reports are welcome once the repository is public.  
Please test locally before submitting.

---

## License

This project is licensed under the **GNU General Public License v2.0 or later (GPL-2.0+)**.  
See the `LICENSE` file for details.
