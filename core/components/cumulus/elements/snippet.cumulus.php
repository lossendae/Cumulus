<?php
/**
 * Cumulus
 *
 * Copyright 2009 by Stephane Boulard <lossendae@gmail.com>
 *
 * This file is part of Cumulus, a flash component for MODx Revolution to show your site tags rotating in 3D.
 *
 * Cumulus is free software; you can redistribute it and/or modify it under the
 * terms of the GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the License, or (at your option) any later
 * version.
 *
 * Cumulus is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * Cumulus; if not, write to the Free Software Foundation, Inc., 59 Temple Place,
 * Suite 330, Boston, MA 02111-1307 USA
 *
 * @package cumulus
 */
/**
 * Cumulus
 *
 * Flash component to show your site tags rotating in 3D
 *
 * @name Cumulus
 * @author Stephane Boulard <lossendae@gmail.com>
 * @package cumulus
 */
$Cumulus = $modx->getService('cumulus','Cumulus',$modx->getOption('cumulus.core_path',null,$modx->getOption('core_path').'components/cumulus/').'model/cumulus/',$scriptProperties);
if (!($Cumulus instanceof Cumulus)) return 'Cumulus controller could not be loaded';

return $Cumulus->generate();