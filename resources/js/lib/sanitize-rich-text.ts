export function sanitizeRichTextHtml(value?: string | null) {
    if (!value) {
        return '';
    }

    if (typeof DOMParser === 'undefined') {
        return value.replace(/<[^>]+>/g, '');
    }

    const parser = new DOMParser();
    const document = parser.parseFromString(value, 'text/html');
    const allowedTags = new Set([
        'P',
        'BR',
        'STRONG',
        'B',
        'EM',
        'I',
        'U',
        'UL',
        'OL',
        'LI',
        'A',
        'BLOCKQUOTE',
        'H1',
        'H2',
        'H3',
        'H4',
        'H5',
        'H6',
    ]);

    const sanitizeStyle = (style: string | null) => {
        if (!style) {
            return null;
        }

        const match = style.match(/text-align\s*:\s*(left|center|right|justify)\s*;?/i);

        return match ? `text-align: ${match[1].toLowerCase()};` : null;
    };

    const sanitizeNode = (node: Node): Node | null => {
        if (node.nodeType === Node.TEXT_NODE) {
            return document.createTextNode(node.textContent ?? '');
        }

        if (node.nodeType !== Node.ELEMENT_NODE) {
            return null;
        }

        const element = node as HTMLElement;

        if (!allowedTags.has(element.tagName)) {
            const fragment = document.createDocumentFragment();

            Array.from(element.childNodes)
                .map(sanitizeNode)
                .filter((child): child is Node => child !== null)
                .forEach((child) => fragment.appendChild(child));

            return fragment;
        }

        const cleanElement = document.createElement(element.tagName.toLowerCase());

        if (element.tagName === 'A') {
            const href = element.getAttribute('href') ?? '';

            if (/^(https?:|mailto:|tel:|\/)/i.test(href)) {
                cleanElement.setAttribute('href', href);
                cleanElement.setAttribute('target', '_blank');
                cleanElement.setAttribute('rel', 'noreferrer noopener');
            }
        }

        const safeStyle = sanitizeStyle(element.getAttribute('style'));

        if (safeStyle) {
            cleanElement.setAttribute('style', safeStyle);
        }

        Array.from(element.childNodes)
            .map(sanitizeNode)
            .filter((child): child is Node => child !== null)
            .forEach((child) => cleanElement.appendChild(child));

        return cleanElement;
    };

    const container = document.createElement('div');

    Array.from(document.body.childNodes)
        .map(sanitizeNode)
        .filter((child): child is Node => child !== null)
        .forEach((child) => container.appendChild(child));

    return container.innerHTML;
}
