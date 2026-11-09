// Shapes of the `wavykit` shared prop (app/WavyKit/SharedProps.php).

/** wavykit/pages breadcrumb output. */
export type Breadcrumb = {
    pageId: string;
    label: string;
    url: string | null;
    current: boolean;
};

/** wavykit/navigation MenuItem output. */
export type MenuItem = {
    pageId: string | null;
    label: string;
    url: string | null;
    current: boolean;
    active: boolean;
    icon: string | null;
    kind: 'page' | 'group';
    children: MenuItem[];
};

export type WavyKit = {
    pageId: string | null;
    breadcrumbs: Breadcrumb[];
    menus: {
        primary: MenuItem[];
        footer: MenuItem[];
    };
};
