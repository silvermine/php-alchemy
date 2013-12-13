<?php

namespace Alchemy\tests;
use Alchemy\orm\DataMapper;


class Language extends DataMapper {
    protected static $props = array(
        'LanguageID' => 'Integer(primary_key = true)',
        'ISO2Code' => 'String(2, unique = true)',
        'LatestChangeStamp' => 'Timestamp',
    );
}