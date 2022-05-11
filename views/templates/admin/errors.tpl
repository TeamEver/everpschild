{*
* Project : everpschild
* @author Team EVER
* @copyright Team EVER
* @license   Tous droits réservés / Le droit d'auteur s'applique (All rights reserved / French copyright law applies)
* @link https://www.team-ever.com
*}
{foreach from=$errors item=msg}
<div class="alert alert-danger" role="alert">
  {$msg|escape:'htmlall':'UTF-8'}
</div>
{/foreach}