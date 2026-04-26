
const { useEffect, useState, useRef } = React;

function RecoBar({ products, lang }) {
  const scrollRef = useRef(null);
  const rawBasePath = typeof window !== 'undefined' && typeof window.AO_BASE_PATH === 'string' ? window.AO_BASE_PATH : '';
  const basePath = rawBasePath.replace(/\/+$/, '');
  const uploadsBase = `${basePath}/uploads`;
  const makeBuyHref = (ref) => `${basePath}/actions/buy_now.php?ref=${encodeURIComponent(ref)}`;
  const getRef = (p) => String(p?.reference ?? p?.ref ?? p?.reference_produit ?? '').trim();
  const normalizeImageSrc = (value) => {
    const src = String(value ?? '').trim();
    if (!src) return '';
    if (/^https?:\/\//i.test(src) || src.startsWith('data:')) return src;
    if (src.startsWith('/')) return `${basePath}${src}`;
    if (src.startsWith('../') || src.startsWith('./')) return src;
    return `${uploadsBase}/${src}`;
  };
  const getImageSrc = (p) => {
    const fromServer = normalizeImageSrc(p?.image_url);
    if (fromServer) return fromServer;
    return normalizeImageSrc(p?.photo);
  };
  const validProducts = (Array.isArray(products) ? products : []).filter((p) => getRef(p).length > 0);
  const labels = {
    fr: { title: '⭐ MEILLEURES VENTES', buy: 'Acheter' },
    en: { title: '⭐ BEST SELLERS',     buy: 'Buy'     },
  }[lang] || { title: '⭐ BEST SELLERS', buy: 'Buy' };

  // Animation horizontale subtile au montage
  useEffect(() => {
    const el = scrollRef.current;
    if (!el) return;
    let direction = 1;
    const interval = setInterval(() => {
      if (!el) return;
      const max = el.scrollWidth - el.clientWidth;
      if (el.scrollLeft >= max - 1) direction = -1;
      if (el.scrollLeft <= 0) direction = 1;
      el.scrollLeft += direction * 0.6;
    }, 40);

    // Stoppe l'auto-scroll des qu'un utilisateur interagit (desktop + mobile).
    const stop = () => clearInterval(interval);
    el.addEventListener('mouseenter', stop);
    el.addEventListener('pointerdown', stop);
    el.addEventListener('touchstart', stop, { passive: true });
    el.addEventListener('focusin', stop);

    return () => {
      clearInterval(interval);
      el.removeEventListener('mouseenter', stop);
      el.removeEventListener('pointerdown', stop);
      el.removeEventListener('touchstart', stop);
      el.removeEventListener('focusin', stop);
    };
  }, []);

  return (
    <div className="reco-bar">
      <div className="reco-bar-title">{labels.title}</div>
      <div className="reco-scroll" ref={scrollRef}>
        {validProducts.map((p) => {
          const ref = getRef(p);
          const imageSrc = getImageSrc(p);
          const discountPercent = Number(p.discount_percent ?? 0);
          const originalPrice = Number(p.prix_original ?? p.prix ?? 0);
          const reducedPrice = Number(p.prix_reduit ?? p.prix ?? 0);
          return (
          <div key={ref} className="reco-card">
            <div className="reco-photo-wrap">
              {imageSrc
                ? <>
                    <img src={imageSrc} alt={String(p?.designation ?? '')} onError={(e) => {
                      e.currentTarget.style.display = 'none';
                      const fallback = e.currentTarget.nextElementSibling;
                      if (fallback) fallback.style.display = 'flex';
                    }} />
                    <div className="reco-photo-fallback" style={{ display: 'none' }}>📦</div>
                  </>
                : <div className="reco-photo-fallback">📦</div>}
            </div>
            <div className="reco-name">{lang === 'en' && p.designation_en ? p.designation_en : p.designation}</div>
            <div className="price-stack" style={{ alignItems: 'center' }}>
              {discountPercent > 0 && (
                <div className="price-old">
                  {originalPrice.toLocaleString('fr-TN', { minimumFractionDigits: 2 })} DT
                </div>
              )}
              <div className="price-tag">
                {reducedPrice.toLocaleString('fr-TN', { minimumFractionDigits: 2 })} DT
              </div>
            </div>
            <a
              className="btn-primary"
              style={{ marginTop: '0.5rem', width: '100%', justifyContent: 'center', fontSize: '0.8rem', padding: '0.4rem 0.75rem' }}
              href={makeBuyHref(ref)}
            >
              {labels.buy}
            </a>
          </div>
        );
        })}
      </div>
    </div>
  );
}

// Montage : window.AO_RECO contient { products, lang }, injecté par index.php
const root = document.getElementById('reco-root');
if (root && window.AO_RECO) {
  ReactDOM.createRoot(root).render(<RecoBar {...window.AO_RECO} />);
}
