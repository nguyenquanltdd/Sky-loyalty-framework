<?php
/**
 * Copyright © 2018 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */

if (isset($_ENV['BOOTSTRAP_PHING_SETUP'])) {
    passthru(sprintf('composer install'));
    passthru(sprintf(
        'php "%s/../bin/console" cache:clear --env=%s --no-warmup',
        __DIR__,
        'prod'
    ));
    passthru(sprintf('phing setup'));
}

require __DIR__.'/../app/autoload.php';
