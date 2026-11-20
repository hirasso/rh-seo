const wpPot = require('wp-pot');

wpPot({
  destFile: './lang/rhseo.pot',
  domain: 'rhseo',
  package: 'RH SEO',
  src: ['rh-seo.php', 'lib/rah/**/*.php']
});
