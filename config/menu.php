<?php

return [
    'items' => [
        // ================== PROJECT MANAGEMENT ==================
        [
            'key'         => 'budget_parents',
            'label'       => 'Budget Parents',
            'route'       => 'erp.budget-parents.index',
            'group'       => 'project',
            'icon'        => 'bx bx-wallet',
            'permissions' => ['budget_parents.view', 'budget_parents.create', 'budget_parents.update', 'budget_parents.delete'],
        ],
        [
            'key'         => 'sub_projects',
            'label'       => 'Sub Projects',
            'route'       => 'erp.sub-projects.index',
            'group'       => 'project',
            'icon'        => 'bx bx-folder',
            'permissions' => ['sub_projects.view', 'sub_projects.create', 'sub_projects.update', 'sub_projects.delete'],
        ],
        [
            'key'         => 'work_items',
            'label'       => 'Work Items (WID)',
            'route'       => 'erp.work-items.index',
            'group'       => 'project',
            'icon'        => 'bx bx-task',
            'permissions' => ['work_items.view', 'work_items.create', 'work_items.update', 'work_items.delete'],
        ],

        // ================== PROCUREMENT & GA ==================
        [
            'key'         => 'request_forms',
            'label'       => 'Request Forms (RF)',
            'route'       => 'erp.request-form.index',
            'group'       => 'procurement',
            'icon'        => 'bx bx-file-blank',
            'permissions' => ['request_forms.view', 'request_forms.create', 'request_forms.update', 'request_forms.delete', 'request_forms.submit', 'request_forms.approve', 'request_forms.reject'],
        ],
        [
            'key'         => 'purchase_orders',
            'label'       => 'Purchase Orders (PO)',
            'route'       => 'erp.purchase-orders.index',
            'group'       => 'procurement',
            'icon'        => 'bx bx-receipt',
            'permissions' => ['purchase_orders.view', 'purchase_orders.create', 'purchase_orders.update', 'purchase_orders.delete', 'purchase_orders.verify', 'purchase_orders.submit', 'purchase_orders.approve', 'purchase_orders.reject', 'purchase_orders.print'],
        ],
        [
            'key'         => 'goods_receipts',
            'label'       => 'Goods Receipts (GRN)',
            'route'       => 'erp.goods-receipts.index',
            'group'       => 'procurement',
            'icon'        => 'bx bx-download',
            'permissions' => ['goods_receipts.view', 'goods_receipts.create', 'goods_receipts.update', 'goods_receipts.delete', 'goods_receipts.verify', 'goods_receipts.print'],
        ],
        [
            'key'         => 'suppliers',
            'label'       => 'Suppliers & Vendors',
            'route'       => 'erp.suppliers.index',
            'group'       => 'procurement',
            'icon'        => 'bx bx-truck',
            'permissions' => ['suppliers.view', 'suppliers.create', 'suppliers.update', 'suppliers.delete'],
        ],
        [
            'key'         => 'payment_terms',
            'label'       => 'Payment Terms (TOP)',
            'route'       => 'erp.payment-terms.index',
            'group'       => 'procurement',
            'icon'        => 'bx bx-credit-card',
            'permissions' => ['payment_terms.view', 'payment_terms.create', 'payment_terms.update', 'payment_terms.delete'],
        ],

        // ================== FINANCE & ACCOUNTING ==================
        [
            'key'         => 'payment_advices',
            'label'       => 'Payment Advices (PA)',
            'route'       => 'erp.payment-advices.index',
            'group'       => 'finance',
            'icon'        => 'bx bx-money',
            'permissions' => ['payment_advices.view', 'payment_advices.update', 'payment_advices.delete'],
        ],
        [
            'key'         => 'payment_advice_details',
            'label'       => 'Supplier Invoices (SID)',
            'route'       => 'erp.payment-advices.index',
            'group'       => 'finance',
            'icon'        => 'bx bx-detail',
            'permissions' => ['payment_advice_details.view', 'payment_advice_details.update_invoice', 'payment_advice_details.submit', 'payment_advice_details.approve', 'payment_advice_details.reject', 'payment_advice_details.mark_paid'],
        ],

        // ================== INVENTORY & LOGISTIK ==================
        [
            'key'         => 'stocks',
            'label'       => 'Stock Overview',
            'route'       => 'erp.stocks.index',
            'group'       => 'inventory',
            'icon'        => 'bx bx-layer',
            'permissions' => ['stocks.view', 'stocks.adjust'],
        ],
        [
            'key'         => 'warehouses',
            'label'       => 'Warehouses & Depo',
            'route'       => 'erp.warehouses.index',
            'group'       => 'inventory',
            'icon'        => 'bx bx-store',
            'permissions' => ['warehouses.view', 'warehouses.create', 'warehouses.update', 'warehouses.delete'],
        ],
        [
            'key'         => 'products',
            'label'       => 'Product Catalog',
            'route'       => 'erp.products.index',
            'group'       => 'inventory',
            'icon'        => 'bx bx-cube-alt',
            'permissions' => ['products.view', 'products.create', 'products.update', 'products.delete', 'products.export'],
        ],
        [
            'key'         => 'product_families',
            'label'       => 'Product Families',
            'route'       => 'erp.product-families.index',
            'group'       => 'inventory',
            'icon'        => 'bx bx-category',
            'permissions' => ['product_families.view', 'product_families.create', 'product_families.update', 'product_families.delete'],
        ],
        [
            'key'         => 'product_types',
            'label'       => 'Product Types',
            'route'       => 'erp.product-types.index',
            'group'       => 'inventory',
            'icon'        => 'bx bx-purchase-tag',
            'permissions' => ['product_types.view', 'product_types.create', 'product_types.update', 'product_types.delete'],
        ],
        [
            'key'         => 'brands',
            'label'       => 'Brands',
            'route'       => 'erp.brands.index',
            'group'       => 'inventory',
            'icon'        => 'bx bx-bookmark',
            'permissions' => ['brands.view', 'brands.create', 'brands.update', 'brands.delete'],
        ],
        [
            'key'         => 'product_models',
            'label'       => 'Product Models',
            'route'       => 'erp.product-models.index',
            'group'       => 'inventory',
            'icon'        => 'bx bx-box',
            'permissions' => ['product_models.view', 'product_models.create', 'product_models.update', 'product_models.delete'],
        ],
        [
            'key'         => 'currencies',
            'label'       => 'Currencies',
            'route'       => 'erp.currencies.index',
            'group'       => 'inventory',
            'icon'        => 'bx bx-dollar-circle',
            'permissions' => ['currencies.view', 'currencies.create', 'currencies.update', 'currencies.delete'],
        ],
        [
            'key'         => 'uoms',
            'label'       => 'Units of Measure (UOM)',
            'route'       => 'erp.uoms.index',
            'group'       => 'inventory',
            'icon'        => 'bx bx-ruler',
            'permissions' => ['uoms.view', 'uoms.create', 'uoms.update', 'uoms.delete'],
        ],

        // ================== SYSTEM & SECURITY ==================
        [
            'key'         => 'users',
            'label'       => 'Users Management',
            'route'       => 'erp.users.index',
            'group'       => 'master',
            'icon'        => 'bx bx-user',
            'permissions' => ['users.view', 'users.create', 'users.update', 'users.delete'],
        ],
        [
            'key'         => 'roles',
            'label'       => 'Roles & Permissions',
            'route'       => 'erp.roles.index',
            'group'       => 'master',
            'icon'        => 'bx bx-shield-quarter',
            'permissions' => ['roles.view', 'roles.create', 'roles.update', 'roles.delete'],
        ],
        [
            'key'         => 'projects',
            'label'       => 'Projects (Tenants)',
            'route'       => 'erp.projects.index',
            'group'       => 'master',
            'icon'        => 'bx bx-buildings',
            'permissions' => ['projects.view', 'projects.create', 'projects.update', 'projects.delete'],
        ],
        [
            'key'         => 'approval_configs',
            'label'       => 'Approval & Verif Configs',
            'route'       => 'erp.approval-configs.index',
            'group'       => 'master',
            'icon'        => 'bx bx-check-shield',
            'permissions' => ['approval_configs.view', 'approval_configs.create', 'approval_configs.delete'],
        ],
    ],

    'groups' => [
        'project'     => ['label' => 'Project Management', 'icon' => 'bx bx-folder-open'],
        'procurement' => ['label' => 'Procurement & GA',    'icon' => 'bx bx-cart'],
        'finance'     => ['label' => 'Finance & Accounting','icon' => 'bx bx-dollar'],
        'inventory'   => ['label' => 'Inventory & Catalog', 'icon' => 'bx bx-box'],
        'master'      => ['label' => 'System & Security',   'icon' => 'bx bx-slider-alt'],
    ],

    'home_candidates' => [
        ['label' => 'Dashboard', 'route' => 'dashboard'],
        ['label' => 'Project Management', 'route' => 'erp.work-items.index'],
        ['label' => 'Procurement PO', 'route' => 'erp.purchase-orders.index'],
        ['label' => 'Goods Receipts (GR)', 'route' => 'erp.goods-receipts.index'],
        ['label' => 'Finance Payment Advices', 'route' => 'erp.payment-advices.index'],
        ['label' => 'Logistik Stocks', 'route' => 'erp.stocks.index'],
    ]
];

