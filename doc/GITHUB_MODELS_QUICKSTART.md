# GitHub Models - Guía Rápida de Uso

**Fecha**: 10 de marzo de 2026  
**Sistema**: Portfolio - OpenAI Provider con GitHub Models

---

## 📋 ¿Qué es GitHub Models?

GitHub Models proporciona acceso **gratuito** a modelos de IA de última generación (GPT-4o, GPT-4o-mini, Llama 3.1, Mistral) a través de la infraestructura Azure AI, usando solo tu GitHub Personal Access Token.

**Ventajas**:
- ✅ Sin tarjeta de crédito necesaria
- ✅ Acceso a GPT-4o y GPT-4o-mini
- ✅ Uso gratuito dentro de límites razonables
- ✅ Ideal para desarrollo y proyectos personales
- ✅ Misma API que OpenAI (compatible)

---

## 🚀 Configuración en 3 Pasos

### Paso 1: Generar GitHub Personal Access Token

1. Ve a: https://github.com/settings/tokens
2. Click en **"Generate new token"** → **"Generate new token (classic)"**
3. Dale un nombre descriptivo: `Portfolio-AI-Models`
4. Selecciona el scope: **`models:read`** (o marca `repo` si es classic)
5. Click en **"Generate token"**
6. **¡COPIA EL TOKEN AHORA!** (no podrás verlo después)

### Paso 2: Configurar en el Proyecto

**Opción A: Variable de Entorno** (Recomendado)
```bash
# Windows PowerShell
$env:GITHUB_TOKEN = "github_pat_XXX..."

# Linux/Mac
export GITHUB_TOKEN="github_pat_XXX..."

# O agregar en archivo .env del proyecto
GITHUB_TOKEN=github_pat_XXX...
```

**Opción B: Archivo config.local.php**
```php
// Editar: admin/config/config.local.php
function get_ai_config() {
    return [
        'enabled' => true,
        'default_provider' => 'openai',  // ⚠️ Cambiar a openai
        
        'api_keys' => [
            'github_models' => 'github_pat_XXX...',  // ⚠️ Pegar tu token aquí
        ],
        
        'github_models' => [
            'enabled' => true,
            'preferred_model' => 'gpt-4o-mini',  // Modelo por defecto
        ]
    ];
}
```

### Paso 3: Usar en el Admin

1. Ve al panel de administración: `http://localhost/admin`
2. Navega a **"Artículos"** → **"Crear Nuevo"**
3. En la sección de generación con IA:
   - Proveedor: Selecciona **"OpenAI (GitHub Models)"**
   - Modelo: Selecciona `gpt-4o-mini` o `gpt-4o`
4. Escribe tu prompt y genera contenido

---

## 🎯 Modelos Disponibles

| Modelo | Descripción | Max Tokens | Uso Recomendado |
|--------|-------------|------------|------------------|
| **gpt-4o-mini** | Rápido y eficiente | 128K | Artículos, descripciones cortas |
| **gpt-4o** | Más potente | 128K | Contenido complejo, análisis |
| **meta-llama-3.1-405b-instruct** | Llama de Meta | 128K | Alternativa open source |
| **mistral-large-2407** | Mistral Large | 128K | Balance rendimiento/costo |

**Modelo por defecto**: `gpt-4o-mini` (si usas GitHub Token)

---

## 💻 Uso Programático

### Desde PHP

```php
<?php
require_once 'admin/classes/AIContentGenerator.php';

$generator = new AIContentGenerator();

// El sistema detecta automáticamente GitHub Token
$result = $generator->generate(
    'Escribe un artículo sobre inteligencia artificial', 
    [
        'provider' => 'openai',  // Usará GitHub Models si GITHUB_TOKEN existe
        'model' => 'gpt-4o-mini',
        'max_tokens' => 1000,
        'temperature' => 0.7,
        'system' => 'Eres un experto en tecnología'
    ]
);

echo $result['content'];
echo "\nModelo usado: " . $result['model'];
echo "\nTokens: " . $result['tokens_used'];
?>
```

### Desde línea de comandos (cURL)

```bash
# Con GitHub Token
curl -X POST https://models.inference.ai.azure.com/chat/completions \
  -H "Authorization: Bearer $GITHUB_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "model": "gpt-4o-mini",
    "messages": [
      {"role": "user", "content": "Hola, ¿cómo estás?"}
    ]
  }'
```

---

## 🔍 Verificar Configuración

### Comprobar si GitHub Token está configurado

```php
<?php
// Crear archivo: admin/test-github-models.php
define('ADMIN_ACCESS', true);
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/classes/AIProviders.php';

$provider = new OpenAIProvider();

if ($provider->isAvailable()) {
    echo "✅ OpenAI Provider disponible\n";
    echo "Modo: " . $provider->getProviderType() . "\n";
    echo "Display Name: " . $provider->getDisplayName() . "\n";
    
    $models = $provider->getAvailableModels();
    echo "\nModelos disponibles:\n";
    foreach ($models as $key => $model) {
        echo "  - {$key}: {$model['name']}\n";
    }
} else {
    echo "❌ OpenAI Provider NO disponible\n";
    echo "Verifica que GITHUB_TOKEN o OPENAI_API_KEY esté configurado\n";
}
?>
```

---

## ⚠️ Límites y Restricciones

### Rate Limits de GitHub Models (Free Tier)
- **Requests por minuto**: ~15
- **Requests por día**: ~150
- **Tokens por request**: Variable según modelo

### Recomendaciones
- ✅ **Ideal para**: Desarrollo, portfolios, blogs personales
- ⚠️ **No ideal para**: Aplicaciones de producción con alto tráfico
- 💡 **Consejo**: Para producción, considera migrar a OpenAI directo o Groq

### Estrategia de Fallback
Si excedes límites, el sistema puede fallar. Implementa fallback a Groq:

```php
try {
    $result = $generator->generate($prompt, ['provider' => 'openai']);
} catch (Exception $e) {
    // Si falla GitHub Models, usar Groq
    error_log("GitHub Models falló, usando Groq: " . $e->getMessage());
    $result = $generator->generate($prompt, ['provider' => 'groq']);
}
```

---

## 🐛 Troubleshooting

### Error: "API key no configurada"
**Solución**: Verifica que `GITHUB_TOKEN` esté en variables de entorno o `config.local.php`

### Error: "HTTP 401 Unauthorized"
**Solución**: 
1. Verifica que el token sea válido
2. Asegúrate de tener scope `models:read`
3. Regenera el token si es necesario

### Error: "HTTP 429 Too Many Requests"
**Solución**: Has excedido el rate limit. Espera unos minutos o usa otro proveedor.

### No veo modelos de GitHub en el admin
**Solución**: 
1. Verifica que `GITHUB_TOKEN` esté configurado **ANTES** que `OPENAI_API_KEY`
2. El sistema prioriza GitHub Token si ambos están presentes

---

## 📚 Referencias

- **GitHub Models Docs**: https://docs.github.com/en/github-models
- **Azure OpenAI API**: https://learn.microsoft.com/azure/ai-services/openai/reference
- **Rate Limits**: https://docs.github.com/en/github-models/usage-limits
- **Generar Token**: https://github.com/settings/tokens

---

## ✅ Checklist de Implementación

- [ ] Token generado en GitHub Settings
- [ ] Token configurado en `GITHUB_TOKEN` o `config.local.php`
- [ ] Proveedor "openai" seleccionado en admin
- [ ] Modelo `gpt-4o-mini` o `gpt-4o` seleccionado
- [ ] Test de generación exitoso
- [ ] Documentar límites de uso para el equipo

---

**¿Necesitas ayuda?** Consulta el archivo principal de análisis: `doc/github-models-analisis-implementacion.md`
