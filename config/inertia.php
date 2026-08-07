<?php

return [

    'testing' => [

        /*
         * Onde assertInertia()->component() procura o arquivo da página.
         *
         * O padrão do pacote é resources/js/Pages (maiúsculo). O starter kit
         * React usa resources/js/pages, então sem isto toda asserção de
         * componente falha com "page component file does not exist".
         */
        'page_paths' => [
            resource_path('js/pages'),
        ],

        'page_extensions' => [
            'js',
            'jsx',
            'svelte',
            'ts',
            'tsx',
            'vue',
        ],

    ],

];
