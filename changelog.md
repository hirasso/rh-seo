#### 1.1.1 (2021-08-17)

- Support for polylang (#2b9ac52)

#### 1.1.0 (2021-08-17)

- only filter meta tags on frontend (#bee4ba5)
- New filters: `rhseo/queried_object` and `rhseo/is_front_page` (#ccd8d40)
- Compatibility with WP 5.7 (#4cd385a)

#### 1.0.9 (2021-07-20)

- Don't show the SEO Metabox on non-public post types (#011111a)
- Rename Global Options Page (#b3cf24f)

#### 1.0.8 (2021-03-24)

- Add new `boolean` filter `rhseo/render_meta_tags` (#7403f04)

#### 1.0.7 (2021-02-23)

- don't fall back for to wp values for `description` (#e92b761)

#### 1.0.5 (2021-02-17)

- Check for existance of $wp_query before calling `get_queried_object()` (#dba74ce)
- Remove unused styles (#91e3c25)
- Add and item to the admin bar on Post Type Options Pages (#1059706)
- - Set taxonomies with object type 'attachment' to private - Hide 'noindex' field on post type settings (#97b4f0d)
- Options for post types with archives (#a138ab2)
- optimize fields (#b272529)
- fix document title for posts (#6d7d4c2)

#### 1.0.4 (2021-02-10)

- prevent infinite loops in options filters (#b0ef7da)

#### 1.0.3 (2021-02-03)

- Disable Yoast on plugin activation (#f97b785)

#### 1.0.2 (2021-02-03)

- sitemap: `inject_meta_query_noindex` for posts and terms (#6ac00d2)
- Option to hide posts from search engines (robots, wp-sitemap.xml) (#e0ab2f2)
- Redirect Attachments (#6216347)
- Remove `users` from `wp-sitemap.xml` (#be5c576)
- add German translation (#e793cd2)
- Support for terms, deeper integration with qTranslate (#d9afd2d)
- Compatibility with YOAST SEO (#c9c5f24)

#### 1.0.1 (2020-12-08)

- Support for qtranslate-xt (#6bc0b27)

#### 1.0.0 (2020-07-28)

- Add SEO Meta Tags (#66180cb)
- Initial commit (#de4d67f)

