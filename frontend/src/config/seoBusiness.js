import { API_ENDPOINTS, urlApi } from "../Services/urls";

const BUSINESS_SEO = {
  name: "JCMS - Soluciones Full Stack con IA Generativa",
  phone: "+34618309775",
  email: "juancmaciassalvador@gmail.com",
  description: "Desarrollador Full Stack especializado en React, PHP y soluciones con Inteligencia Artificial Generativa.",
  serviceType: "Full Stack Development",
  priceRange: "€€",
  streetAddress: "Calle de Padre Oltra",
  addressLocality: "Madrid",
  addressRegion: "Comunidad de Madrid",
  postalCode: "28019",
  addressCountry: "ES",
  geo: {
    latitude: 40.3861,
    longitude: -3.7161
  },
  sameAs: [
    "https://www.linkedin.com/in/juancarlosmacias/",
    "https://github.com/juancmacias",
    "https://maps.app.goo.gl/eb43KR6oPFGrNgAn9",
    "https://play.google.com/store/apps/dev?id=7098282899285176966",
    "https://www.instagram.com/jcms_madrid/"
  ]
};

const SEO_BUSINESS_CACHE_KEY = "portfolio:seo-business-config";
const SEO_BUSINESS_CACHE_TTL_MS = 6 * 60 * 60 * 1000;

const normalizeBaseUrl = (baseUrl) => {
  if (!baseUrl) {
    return "https://www.juancarlosmacias.es";
  }

  return baseUrl.endsWith("/") ? baseUrl.slice(0, -1) : baseUrl;
};

const normalizeBusinessSeo = (raw = {}) => {
  const normalized = {
    ...BUSINESS_SEO,
    ...raw
  };

  // Normalizar sameAs: solo URLs válidas, sin duplicados
  const sameAs = Array.isArray(normalized.sameAs) ? normalized.sameAs : [];
  normalized.sameAs = Array.from(
    new Set(
      sameAs
        .filter((url) => typeof url === "string")
        .map((url) => url.trim())
        .filter((url) => /^https?:\/\//i.test(url))
    )
  );

  // Normalizar geo
  if (normalized.geo && typeof normalized.geo === "object") {
    normalized.geo = {
      latitude: parseFloat(normalized.geo.latitude) || BUSINESS_SEO.geo.latitude,
      longitude: parseFloat(normalized.geo.longitude) || BUSINESS_SEO.geo.longitude
    };
  } else {
    normalized.geo = { ...BUSINESS_SEO.geo };
  }

  return normalized;
};

const readCachedBusinessSeo = () => {
  try {
    const cachedRaw = localStorage.getItem(SEO_BUSINESS_CACHE_KEY);
    if (!cachedRaw) {
      return null;
    }

    const cached = JSON.parse(cachedRaw);
    const isFresh = Date.now() - cached.savedAt < SEO_BUSINESS_CACHE_TTL_MS;

    if (!isFresh || !cached.data) {
      localStorage.removeItem(SEO_BUSINESS_CACHE_KEY);
      return null;
    }

    return normalizeBusinessSeo(cached.data);
  } catch (error) {
    return null;
  }
};

const writeCachedBusinessSeo = (data) => {
  try {
    localStorage.setItem(
      SEO_BUSINESS_CACHE_KEY,
      JSON.stringify({
        savedAt: Date.now(),
        data: normalizeBusinessSeo(data)
      })
    );
  } catch (error) {
    // no-op
  }
};

export const getBusinessSeoFallback = () => normalizeBusinessSeo(BUSINESS_SEO);

export const fetchBusinessSeoConfig = async () => {
  const cached = readCachedBusinessSeo();
  if (cached) {
    return cached;
  }

  try {
    // Usa el endpoint dedicado y ligero en lugar del metadata general
    const response = await fetch(API_ENDPOINTS.portfolio.seoConfig, { cache: "no-store" });
    if (!response.ok) {
      return getBusinessSeoFallback();
    }

    const json = await response.json();
    // El endpoint dedicado devuelve los datos directamente en data
    const seoBusiness = json?.data;
    if (!seoBusiness || typeof seoBusiness !== "object") {
      return getBusinessSeoFallback();
    }

    const normalized = normalizeBusinessSeo(seoBusiness);
    writeCachedBusinessSeo(normalized);
    return normalized;
  } catch (error) {
    return getBusinessSeoFallback();
  }
};

export const getProfessionalServiceStructuredData = (businessSeoConfig = BUSINESS_SEO) => {
  const baseUrl = normalizeBaseUrl(urlApi);
  const businessSeo = normalizeBusinessSeo(businessSeoConfig);

  const schema = {
    "@context": "https://schema.org",
    "@type": "ProfessionalService",
    "@id": `${baseUrl}/#professional-service`,
    name: businessSeo.name,
    url: `${baseUrl}/contacto`,
    image: `${baseUrl}/Assets/Projects/portfolio.png`,
    telephone: businessSeo.phone,
    email: businessSeo.email,
    address: {
      "@type": "PostalAddress",
      streetAddress: businessSeo.streetAddress,
      addressLocality: businessSeo.addressLocality,
      addressRegion: businessSeo.addressRegion,
      postalCode: businessSeo.postalCode,
      addressCountry: businessSeo.addressCountry
    },
    areaServed: {
      "@type": "Country",
      name: "España"
    },
    openingHoursSpecification: [
      {
        "@type": "OpeningHoursSpecification",
        dayOfWeek: [
          "https://schema.org/Monday",
          "https://schema.org/Tuesday",
          "https://schema.org/Wednesday",
          "https://schema.org/Thursday",
          "https://schema.org/Friday"
        ],
        opens: "09:00",
        closes: "14:00"
      }
    ],
    sameAs: businessSeo.sameAs
  };

  // Campos opcionales enriquecidos (solo se añaden si tienen valor)
  if (businessSeo.description) {
    schema.description = businessSeo.description;
  }
  if (businessSeo.serviceType) {
    schema.serviceType = businessSeo.serviceType;
  }
  if (businessSeo.priceRange) {
    schema.priceRange = businessSeo.priceRange;
  }
  if (businessSeo.geo && businessSeo.geo.latitude && businessSeo.geo.longitude) {
    schema.geo = {
      "@type": "GeoCoordinates",
      latitude: businessSeo.geo.latitude,
      longitude: businessSeo.geo.longitude
    };
  }

  return schema;
};
