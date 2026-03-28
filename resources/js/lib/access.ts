export function translateRoleName(name: string, t: (key: string) => string): string {
    const key = `roles.names.${name}`;
    const translated = t(key);

    return translated === key ? name : translated;
}

export function translatePermissionName(name: string, t: (key: string) => string): string {
    const key = `permissions.labels.${name}`;
    const translated = t(key);

    return translated === key ? name : translated;
}
