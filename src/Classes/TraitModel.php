<?php
/**
 * Created by PhpStorm.
 * User: sharif ahrari
 * Date: 6/25/2016
 * Time: 5:21 PM
 */

namespace Module\Classes;;


trait TraitModel
{
    public static function getHighestPosition($table)
    {
        return (int)\DB::table($table)->orderBy('position','DESC')->value('position');
    }
}