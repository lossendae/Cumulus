<?php
/**
 * Snippets builder for Cumulus.
 *
 * copyright Copyright (C) 2010, Stephane Boulard <lossendae@gmail.com>
 * 
 * @package cumulus
 */
/**
 * @package cumulus
 * @subpackage build
 */
$snippets = array();

$snippets[1]= $modx->newObject('modSnippet');
$snippets[1]->fromArray(array(
    'id' => 1,
    'name' => 'Cumulus',
    'description' => 'Flash tags cloud components.',
    'snippet' => getSnippetContent($sources['elements'].'snippet.cumulus.php'),
),'',true,true);
$properties = include $sources['properties'].'properties.cumulus.php';

$snippets[1]->setProperties($properties);
unset($properties);

return $snippets;