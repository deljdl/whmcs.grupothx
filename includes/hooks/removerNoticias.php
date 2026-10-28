<?php

add_hook('ClientAreaHomepagePanels', 1, function ($homePagePanels) {
    $homePagePanels->removeChild('Recent News');
});