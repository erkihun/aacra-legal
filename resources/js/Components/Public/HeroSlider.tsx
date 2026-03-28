import { Link } from '@inertiajs/react';
import { useEffect, useState } from 'react';

type HeroSlide = {
    title: string;
    subtitle: string;
    button_label: string;
    button_url: string;
    image_url?: string | null;
};

type HeroSliderProps = {
    slides: HeroSlide[];
    eyebrow: string;
    primaryCtaHref: string;
    primaryCtaLabel: string;
    secondaryCtaHref: string;
    secondaryCtaLabel: string;
    tertiaryCtaHref: string;
    tertiaryCtaLabel: string;
    metrics: Array<{ label: string; value: string }>;
    previousLabel: string;
    nextLabel: string;
};

export default function HeroSlider({
    slides,
    eyebrow,
    primaryCtaHref,
    primaryCtaLabel,
    secondaryCtaHref,
    secondaryCtaLabel,
    tertiaryCtaHref,
    tertiaryCtaLabel,
    metrics,
    previousLabel,
    nextLabel,
}: HeroSliderProps) {
    const safeSlides = slides.length > 0
        ? slides
        : [
            {
                title: '',
                subtitle: '',
                button_label: '',
                button_url: '',
                image_url: null,
            },
        ];

    const [activeIndex, setActiveIndex] = useState(0);

    useEffect(() => {
        if (safeSlides.length <= 1) {
            return undefined;
        }

        const timer = window.setInterval(() => {
            setActiveIndex((current) => (current + 1) % safeSlides.length);
        }, 6500);

        return () => window.clearInterval(timer);
    }, [safeSlides.length]);

    return (
        <section className="relative overflow-hidden rounded-[2rem] border border-[color:var(--border)] bg-[color:var(--surface-strong)] shadow-[var(--shadow-strong)]">
            <div className="absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(14,165,233,0.16),transparent_36%),radial-gradient(circle_at_bottom_right,rgba(15,118,110,0.12),transparent_38%)]" />

            <div className="relative min-h-[34rem] lg:min-h-[40rem]">
                {safeSlides.map((slide, index) => (
                    <div
                        key={`${slide.title}-${index}`}
                        className={`absolute inset-0 transition-[opacity,transform] duration-700 ${index === activeIndex ? 'pointer-events-auto opacity-100' : 'pointer-events-none opacity-0'}`}
                    >
                        <div className="absolute inset-0">
                            {slide.image_url ? <img src={slide.image_url} alt={slide.title} className="h-full w-full object-cover" /> : null}
                            <div className="absolute inset-0 bg-[linear-gradient(110deg,rgba(7,12,26,0.82)_0%,rgba(7,12,26,0.6)_40%,rgba(7,12,26,0.3)_70%,rgba(7,12,26,0.12)_100%)]" />
                            <div className="absolute inset-0 bg-[linear-gradient(180deg,rgba(7,12,26,0.08)_0%,rgba(7,12,26,0.44)_100%)]" />
                        </div>

                        <div className="relative flex h-full flex-col justify-between px-6 py-8 sm:px-8 sm:py-10 lg:px-12 lg:py-12">
                            <div className="max-w-4xl">
                                <div className="inline-flex rounded-full border border-white/15 bg-white/10 px-4 py-2 text-xs font-semibold uppercase text-white/90 backdrop-blur-md">
                                    {eyebrow}
                                </div>

                                <h1 className="mt-6 max-w-3xl text-4xl font-semibold leading-tight tracking-tight text-white sm:text-5xl lg:text-6xl">
                                    {slide.title}
                                </h1>

                                <p className="mt-5 max-w-2xl text-base leading-8 text-white/80 sm:text-lg">
                                    {slide.subtitle}
                                </p>

                                <div className="mt-8 flex flex-wrap gap-3">
                                    {slide.button_label && slide.button_url ? (
                                        <Link href={slide.button_url} className="btn-base bg-white text-slate-900 shadow-[0_18px_40px_-20px_rgba(15,23,42,0.55)] hover:bg-slate-100 focus-ring">
                                            {slide.button_label}
                                        </Link>
                                    ) : null}
                                    <Link href={primaryCtaHref} className="btn-base border border-white/15 bg-white/10 text-white backdrop-blur-md hover:bg-white/15 focus-ring">
                                        {primaryCtaLabel}
                                    </Link>
                                    <Link href={secondaryCtaHref} className="btn-base border border-white/15 bg-transparent text-white hover:bg-white/10 focus-ring">
                                        {secondaryCtaLabel}
                                    </Link>
                                    <Link href={tertiaryCtaHref} className="btn-base border border-white/10 bg-transparent text-white/80 hover:bg-white/10 hover:text-white focus-ring">
                                        {tertiaryCtaLabel}
                                    </Link>
                                </div>
                            </div>

                            <div className="mt-8 grid gap-3 sm:grid-cols-3 lg:max-w-3xl">
                                {metrics.map((metric) => (
                                    <div key={metric.label} className="rounded-[1.4rem] border border-white/12 bg-white/10 px-4 py-4 backdrop-blur-md">
                                        <p className="text-xs uppercase text-white/60">{metric.label}</p>
                                        <p className="mt-2 text-2xl font-semibold text-white">{metric.value}</p>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>
                ))}

                {safeSlides.length > 1 ? (
                    <>
                        <div className="absolute bottom-6 left-6 z-10 flex items-center gap-2 sm:left-8 lg:left-12">
                            {safeSlides.map((slide, index) => (
                                <button
                                    key={`${slide.title}-indicator-${index}`}
                                    type="button"
                                    aria-label={`${index + 1}`}
                                    onClick={() => setActiveIndex(index)}
                                    className={`h-2.5 rounded-full transition-all ${index === activeIndex ? 'w-10 bg-white' : 'w-2.5 bg-white/45 hover:bg-white/70'}`}
                                />
                            ))}
                        </div>

                        <div className="absolute bottom-6 right-6 z-10 flex items-center gap-2 sm:right-8 lg:right-12">
                            <button
                                type="button"
                                aria-label={previousLabel}
                                onClick={() => setActiveIndex((current) => (current - 1 + safeSlides.length) % safeSlides.length)}
                                className="flex h-11 w-11 items-center justify-center rounded-full border border-white/15 bg-white/10 text-white backdrop-blur-md transition hover:bg-white/15 focus-ring"
                            >
                                <svg aria-hidden="true" viewBox="0 0 20 20" className="h-4 w-4 fill-none stroke-current stroke-2">
                                    <path d="M12.5 4.5L7 10l5.5 5.5" strokeLinecap="round" strokeLinejoin="round" />
                                </svg>
                            </button>
                            <button
                                type="button"
                                aria-label={nextLabel}
                                onClick={() => setActiveIndex((current) => (current + 1) % safeSlides.length)}
                                className="flex h-11 w-11 items-center justify-center rounded-full border border-white/15 bg-white/10 text-white backdrop-blur-md transition hover:bg-white/15 focus-ring"
                            >
                                <svg aria-hidden="true" viewBox="0 0 20 20" className="h-4 w-4 fill-none stroke-current stroke-2">
                                    <path d="M7.5 4.5L13 10l-5.5 5.5" strokeLinecap="round" strokeLinejoin="round" />
                                </svg>
                            </button>
                        </div>
                    </>
                ) : null}
            </div>
        </section>
    );
}
