<?php

test('the plugin boots', function () {
    expect(rhseo())->toBeInstanceOf(RAH\SEO\SEO::class);
});

test('the sitemap leaves out users, posts and default taxonomies', function () {
    $server = wp_sitemaps_get_server();

    expect($server->registry->get_providers())->not->toHaveKey('users');

    $postTypes = $server->registry->get_provider('posts')->get_object_subtypes();
    expect($postTypes)->not->toHaveKey('post')->toHaveKey('page');

    $taxonomies = $server->registry->get_provider('taxonomies')->get_object_subtypes();
    expect($taxonomies)->not->toHaveKey('category')->not->toHaveKey('post_tag');
});

test('wp_head renders the meta tags', function () {
    update_option('blogname', 'RH SEO Test');
    $this->go_to(home_url('/'));

    $head = get_echo('wp_head');

    expect($head)
        ->toContain('<meta property="og:site_name" content="RH SEO Test">')
        ->toContain('<meta name="twitter:card" content="summary_large_image">');
});
