<?php return array(
    'root' => array(
        'name' => 'hirasso/rh-seo',
        'pretty_version' => 'dev-main',
        'version' => 'dev-main',
        'reference' => 'e0541f838b15f6ac5879db916c4e990644156c83',
        'type' => 'wordpress-plugin',
        'install_path' => __DIR__ . '/../../../',
        'aliases' => array(),
        'dev' => true,
    ),
    'versions' => array(
        'composer/installers' => array(
            'pretty_version' => 'dev-main',
            'version' => 'dev-main',
            'reference' => '634ba02afdc5a961e0633e63c2c9afd1f6ba3a6d',
            'type' => 'composer-plugin',
            'install_path' => __DIR__ . '/./installers',
            'aliases' => array(
                0 => '2.x-dev',
            ),
            'dev_requirement' => false,
        ),
        'hirasso/rh-seo' => array(
            'pretty_version' => 'dev-main',
            'version' => 'dev-main',
            'reference' => 'e0541f838b15f6ac5879db916c4e990644156c83',
            'type' => 'wordpress-plugin',
            'install_path' => __DIR__ . '/../../../',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
    ),
);
