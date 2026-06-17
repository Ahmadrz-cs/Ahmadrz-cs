<?php

// if (file_exists(dirname(__DIR__) . '/var/cache/prod/App_KernelProdContainer.preload.php')) {
//     require dirname(__DIR__) . '/var/cache/prod/App_KernelProdContainer.preload.php';
// }

// We are not using Symfony Flex namespace yet
if (file_exists(dirname(__DIR__) . '/var/cache/prod/AppKernelProdContainer.preload.php')) {
    require dirname(__DIR__) . '/var/cache/prod/AppKernelProdContainer.preload.php';
}
