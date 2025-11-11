<?php
// components/sidebar_config.php
// Configuration file for different sidebar menus based on user roles and pages

/**
 * Get sidebar menu configuration
 * @param string $menu_type - Type of menu (owner, staff, donor, etc.)
 * @param string $current_page - Current page file name for active state
 * @return array - Menu configuration array
 */
function getSidebarMenu($menu_type, $current_page = '') {
    $menus = [
        'owner' => [
            [
                'label' => 'Overview',
                'items' => [
                    [
                        'label' => 'Dashboard',
                        'url' => 'owner_home.php',
                        'active' => ($current_page === 'owner_home.php')
                    ],
                    [
                        'label' => 'Fraud Management',
                        'url' => 'fraud.php',
                        'active' => ($current_page === 'fraud.php')
                    ]
                ]
            ],
            [
                'label' => 'Management',
                'items' => [
                    [
                        'label' => 'Children',
                        'url' => 'child.php',
                        'active' => ($current_page === 'child.php')
                    ],
                    [
                        'label' => 'Donors',
                        'url' => 'donor.php',
                        'active' => ($current_page === 'donor.php')
                    ],
                    [
                        'label' => 'Donations',
                        'url' => 'donation.php',
                        'active' => ($current_page === 'donation.php')
                    ],
                    [
                        'label' => 'Staff',
                        'url' => 'staff_management.php',
                        'active' => ($current_page === 'staff_management.php')
                    ]
                ]
            ]
        ],
        
        'staff' => [
            [
                'label' => 'Overview',
                'items' => [
                    [
                        'label' => 'Dashboard',
                        'url' => 'staff_home_old.php',
                        'active' => ($current_page === 'staff_home_old.php')
                    ]
                ]
            ],
            [
                'label' => 'Management',
                'items' => [
                    [
                        'label' => 'Children',
                        'url' => '../owner/child.php',
                        'active' => ($current_page === 'child.php')
                    ],
                    [
                        'label' => 'Donors',
                        'url' => '../owner/donor.php',
                        'active' => ($current_page === 'donor.php')
                    ],
                    [
                        'label' => 'Donations',
                        'url' => '../owner/donation.php',
                        'active' => ($current_page === 'donation.php')
                    ]
                ]
            ]
        ],
        
        'donor' => [
            [
                'label' => 'Overview',
                'items' => [
                    [
                        'label' => 'Dashboard',
                        'url' => 'donor_home.php',
                        'active' => ($current_page === 'donor_home.php')
                    ]
                ]
            ],
            [
                'label' => 'My Activity',
                'items' => [
                    [
                        'label' => 'My Sponsorships',
                        'url' => 'my_sponsorships.php',
                        'active' => ($current_page === 'my_sponsorships.php')
                    ],
                    [
                        'label' => 'My Donations',
                        'url' => 'my_donations.php',
                        'active' => ($current_page === 'my_donations.php')
                    ],
                    [
                        'label' => 'Available Children',
                        'url' => 'available_children.php',
                        'active' => ($current_page === 'available_children.php')
                    ]
                ]
            ]
        ],
        
        'sponsor' => [
            [
                'label' => 'Overview',
                'items' => [
                    [
                        'label' => 'Home',
                        'url' => 'sponser_main_page.php',
                        'active' => ($current_page === 'sponser_main_page.php')
                    ],
                    [
                        'label' => 'My Profile',
                        'url' => 'sponser_profile.php',
                        'active' => ($current_page === 'sponser_profile.php')
                    ],
                    [
                        'label' => 'My Home',
                        'url' => 'sponser_home.php',
                        'active' => ($current_page === 'sponser_home.php')
                    ]
                ]
            ],
            [
                'label' => 'Management',
                'items' => [
                    [
                        'label' => 'Sponsor Child',
                        'url' => 'sponsor_child.php',
                        'active' => ($current_page === 'sponsor_child.php')
                    ],
                    [
                        'label' => 'Calendar',
                        'url' => 'sponser_home.php#calendar',
                        'active' => false
                    ]
                ]
            ]
        ]
    ];
    
    return $menus[$menu_type] ?? [];
}

/**
 * Initialize sidebar for a specific page
 * @param string $menu_type - Type of menu (owner, staff, donor, etc.)
 * @param string $current_page - Current page file name (optional, auto-detected if not provided)
 * @return array - Sidebar menu configuration
 */
function initSidebar($menu_type, $current_page = '') {
    // Auto-detect current page if not provided
    if (empty($current_page)) {
        $current_page = basename($_SERVER['PHP_SELF']);
    }
    
    return getSidebarMenu($menu_type, $current_page);
}