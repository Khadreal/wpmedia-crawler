### PHP Technical Assessment Template

### Problems
- Creating a solution that crawl webpages which would help our users achieve their desired seo results.

### Technical Spec
- Created a very simple form on the back that allows site owner to select the page that needs to crawl
- After the page selection, a cron job is created using `wp_schedule_single_event` and `wp_schedule_event` to crawl the selected page

### Technical Decision
- Used `file_get_contents` to get the page instead of `curl`, I decided to go with `file_get_contents` since we're only concern about the data and the header, timeouts and redirects doesn't matter to us.
- Tried using `preg_match_all` which I find a little brittle for this, and then a combination of `strip_tags` and `preg_split` to get the links, however I wasn't satisfy with the result and end up settling for `PHP DOM`, although it throws warning errors for HTML5 `DOCTYPE` but there's an option to suppress it.
- Also, PHP DOM has option to save HTML which is one of the feature required by the user.
- Used `WP` core functions to handle upload, `wp_parse_url` which helps in handling the inconsistency with `parse_url` 
- I used WP `wp_mkdir_p` function to handle directory creation caused it doesn't only create the directory but attempt to set the right permissions, so we don't have to worry about permission.
- Also, I added an option to select page instead of just defaulting the script to find the index(front page), which in my opinion makes it a little bit flexible for the site owner and can be use for other pages.


### How the code works
- It gets the page ID the user selected, with the ID the system get the page url, title and create cron system to crawl the page immediately and schedule hourly. However, every time the schedule is run the data is updated and if it doesn't exist it create it for the first time.
- When the cron runs, it generated the static pages and sitemaps(xml and html) and stored in media library `wp-content\uploads\wp-media-crawler\`. Sitemap files are stored in `wp-content\uploads\wp-media-crawler\sitemap` in uploads directory and static files are in `wp-content\uploads\wp-media-crawler\html\wpmedia_crawl_[page_name].html`.
- I decided to put the files here, so it does not get deleted when the plugin is disabled/deleted.


### Solution Desired
- A page on the admin to see the all the available pages crawled so far, each entry has a link to view the static page, sitemap HTML and XML. On the single page you can see the all the internal links available on the page as at the last time it was crawled.


### Thinking out loud
- Approach -- To solve any problem , one needs to have a clear understanding of the problem as this would help in coming with an optimal solution, and the **What** and **Why** in the readme file really help in understanding this problem.
- Thought about how to get pages(homepage), the ID and how to run the process without affecting website performance of website. Saving/updating the information.
- Used `update_option()` because it help in checking if data is available and if not create it/update.
- I chose this particular approach because it doesn't need any third party library/package, and it solves the problem without much complexity.
- Contemplate saving the links in json(which can be used outside WP) but settled for serialise, currently no plan in using this data out of WP and serialization is widely supported by WP. Maybe next version we can look at it again and decide which option is the best but for now it's serialization.
- I think saving parent tag of the links (h1 > a) as part of the link can lead to better seo decision for the site admin.


### Questions
- The brief doesn't mention any use of the static html file that will be saved, so why are saving them and taking up storage space
- Will the sitemap be generated for all pages or just the homepage alone.
- 
