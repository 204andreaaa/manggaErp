<?php

return [
    'items' => [
        // ================== MASTER DATA ==================
        [
            'key'   => 'users',
            'label' => 'Users',
            'route' => 'erp.users.index',
            'group' => 'master',
            'icon'  => 'bx bx-user',
        ],
        [
            'key'   => 'roles',
            'label' => 'Roles & Permissions',
            'route' => 'erp.roles.index',
            'group' => 'master',
            'icon'  => 'bx bx-shield-quarter',
        ],
        [
            'key'   => 'projects',
            'label' => 'Projects',
            'route' => 'erp.projects.index',
            'group' => 'master',
            'icon'  => 'bx bx-buildings',
        ],
        [
            'key'   => 'warehouses',
            'label' => 'Warehouses',
            'route' => 'erp.warehouses.index',
            'group' => 'master',
            'icon'  => 'bx bx-store',
        ],
        [
            'key'   => 'suppliers',
            'label' => 'Suppliers',
            'route' => 'erp.suppliers.index',
            'group' => 'master',
            'icon'  => 'bx bx-truck',
        ],
        [
            'key'   => 'payment_terms',
            'label' => 'Payment Terms',
            'route' => 'erp.payment-terms.index',
            'group' => 'master',
            'icon'  => 'bx bx-credit-card',
        ],
        [
            'key'   => 'approval_configs',
            'label' => 'Approval Configs',
            'route' => 'erp.approval-configs.index',
            'group' => 'master',
            'icon'  => 'bx bx-check-shield',
        ],

        // ================== INVENTORY (PRODUCTS) ==================
        [
            'key'   => 'products',
            'label' => 'Products',
            'route' => 'erp.products.index',
            'group' => 'inventory',
            'icon'  => 'bx bx-cube-alt',
        ],
        [
            'key'   => 'product_families',
            'label' => 'Product Families',
            'route' => 'erp.product-families.index',
            'group' => 'inventory',
            'icon'  => 'bx bx-category',
        ],
        [
            'key'   => 'product_types',
            'label' => 'Product Types',
            'route' => 'erp.product-types.index',
            'group' => 'inventory',
            'icon'  => 'bx bx-purchase-tag',
        ],
        [
            'key'   => 'brands',
            'label' => 'Brands',
            'route' => 'erp.brands.index',
            'group' => 'inventory',
            'icon'  => 'bx bx-bookmark',
        ],
        [
            'key'   => 'product_models',
            'label' => 'Product Models',
            'route' => 'erp.product-models.index',
            'group' => 'inventory',
            'icon'  => 'bx bx-box',
        ],
        [
            'key'   => 'currencies',
            'label' => 'Currencies',
            'route' => 'erp.currencies.index',
            'group' => 'inventory',
            'icon'  => 'bx bx-dollar-circle',
        ],
        [
            'key'   => 'uoms',
            'label' => 'Units of Measure (UOM)',
            'route' => 'erp.uoms.index',
            'group' => 'inventory',
            'icon'  => 'bx bx-ruler',
        ],

        // ================== PROCUREMENT ==================
        [
            'key'   => 'purchase_orders',
            'label' => 'Purchase Orders',
            'route' => 'erp.purchase-orders.index',
            'group' => 'procurement',
            'icon'  => 'bx bx-receipt',
        ],
        [
            'key'   => 'goods_receipts',
            'label' => 'Goods Receipts (GRN)',
            'route' => 'erp.goods-receipts.index',
            'group' => 'procurement',
            'icon'  => 'bx bx-download',
        ]
    ],

    'groups' => [
        'master'      => ['label' => 'Master Data', 'icon' => 'bx bx-slider-alt'],
        'inventory'   => ['label' => 'Inventory',   'icon' => 'bx bx-box'],
        'procurement' => ['label' => 'Procurement', 'icon' => 'bx bx-cart'],
    ],

    'home_candidates' => [
        ['label' => 'Dashboard', 'route' => 'dashboard'],
    ]
];
