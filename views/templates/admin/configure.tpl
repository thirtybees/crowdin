{*
 * 2017 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 *  @author    thirty bees <modules@thirtybees.com>
 *  @copyright 2017-2024 thirty bees
 *  @license   https://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*}
<div class="panel">
  <h3><i class="icon icon-puzzle-piece"></i> {l s='Crowdin' mod='crowdin'}</h3>
  <strong>{l s='Crowdin in-context translation' mod='crowdin'}</strong>
  <p>
    {l s='Translate live with this module!' mod='crowdin'}
  </p>
  <strong>{l s='Quick start' mod='crowdin'}</strong>
  <ol>
    <li>{l s='Enable either front or back office translations or both' mod='crowdin'}</li>
    <li>{l s='Refresh the page' mod='crowdin'}</li>
    <li>{l s='And start translating!' mod='crowdin'}</li>
  </ol>
  <p>{l s='This module has installed a virtual language called `Zulu`. If you are translating always make sure you have switched to this language, otherwise Crowdin does not recogize the strings.' mod='crowdin'}</p>
  <p>
    {if !$zulu}
      <a class="btn btn-primary" href="{$moduleLink|escape:'htmlall':'UTF-8'}&switchToZulu=1">
        <i class="icon icon-check"></i> {l s='Enable the virtual language' mod='crowdin'}
      </a>
    {else}
      <a class="btn btn-primary" href="{$moduleLink|escape:'htmlall':'UTF-8'}&switchToZulu=0">
        <i class="icon icon-times"></i> {l s='Disable the virtual language' mod='crowdin'}
      </a>
    {/if}
  </p>
</div>
