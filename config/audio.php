<?php
declare( strict_types = 1 );

return [
    'recorder' => 'arecord',
    'args' => '-r 8000 -f S16_LE -D mixin',
    'fileFormat' => '.wav'
];