import { Editor } from '@tinymce/tinymce-react';
import { useEffect, useState } from 'react';
import tinymce from 'tinymce/tinymce';

import 'tinymce/icons/default';
import 'tinymce/models/dom';
import 'tinymce/plugins/advlist';
import 'tinymce/plugins/autoresize';
import 'tinymce/plugins/link';
import 'tinymce/plugins/lists';
import 'tinymce/themes/silver';
import 'tinymce/skins/ui/oxide/skin.min.css';

declare global {
    interface Window {
        tinymce?: typeof tinymce;
    }
}

if (typeof window !== 'undefined' && !window.tinymce) {
    window.tinymce = tinymce;
}

type RichTextEditorProps = {
    value: string;
    onChange: (value: string) => void;
    minHeight?: number;
};

export default function RichTextEditor({
    value,
    onChange,
    minHeight = 320,
}: RichTextEditorProps) {
    const [isDark, setIsDark] = useState(() => document.documentElement.classList.contains('dark'));

    useEffect(() => {
        const root = document.documentElement;
        const observer = new MutationObserver(() => {
            setIsDark(root.classList.contains('dark'));
        });

        observer.observe(root, {
            attributes: true,
            attributeFilter: ['class'],
        });

        return () => observer.disconnect();
    }, []);

    return (
        <div className="rich-text-editor">
            <Editor
                key={isDark ? 'dark' : 'light'}
                licenseKey="gpl"
                value={value}
                onEditorChange={onChange}
                init={{
                    menubar: false,
                    branding: false,
                    promotion: false,
                    statusbar: false,
                    skin: false,
                    content_css: false,
                    resize: false,
                    min_height: minHeight,
                    autoresize_bottom_margin: 16,
                    plugins: ['advlist', 'autoresize', 'link', 'lists'],
                    toolbar:
                        'undo redo | bold italic underline | bullist numlist | alignleft aligncenter alignright alignjustify | link | removeformat',
                    content_style: `
                        body {
                            margin: 1rem;
                            font-family: var(--font-sans);
                            font-size: 14px;
                            line-height: 1.75;
                            color: ${isDark ? '#e2e8f0' : '#0f172a'};
                            background: ${isDark ? '#0f172a' : '#ffffff'};
                        }
                        p { margin: 0 0 0.75rem; }
                        a { color: ${isDark ? '#67e8f9' : '#0f766e'}; }
                    `,
                }}
            />
        </div>
    );
}
