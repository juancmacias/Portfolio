# Análisis de Implementación: GitHub Models como Proveedor de IA

**Fecha**: 9 de marzo de 2026  
**Versión**: 1.0  
**Sistema**: Portfolio - Multi-Provider AI Integration

---

## 📋 Resumen Ejecutivo

GitHub Models permite acceso **gratuito o con límites generosos** a modelos de OpenAI, Meta, Microsoft y otros a través de la infraestructura Azure AI, autenticándose con un Personal Access Token (PAT) de GitHub. Esta integración permitiría:

- ✅ **Acceso a modelos GPT-4o-mini y GPT-4o** sin configurar cuenta OpenAI directa
- ✅ **Autenticación simplificada** con GitHub PAT (scope `models:read`)
- ✅ **Compatible con biblioteca OpenAI** (Python/PHP) cambiando solo `base_url`
- ✅ **Ideal para prototipado** con rate limits razonables
- ⚠️ **Límites de producción**: No recomendado para alto tráfico (ver restricciones)

---

## 🎯 Código Propuesto vs Sistema Actual

### Script Python del Usuario (Referencia)
```python
from openai import OpenAI
import os

client = OpenAI(
    base_url="https://models.inference.ai.azure.com",
    api_key=os.getenv("GITHUB_TOKEN"),  # PAT con scope models:read
)

def generate_text(
    prompt: str,
    model: str = "gpt-4o-mini",
    system: str | None = None,
    temperature: float = 0.7,
):
    messages = []
    if system:
        messages.append({"role": "system", "content": system})
    messages.append({"role": "user", "content": prompt})
    
    response = client.chat.completions.create(
        model=model,
        messages=messages,
        temperature=temperature
    )
    return response.choices[0].message.content
```

### Sistema Actual (PHP)
Tu sistema tiene:
- **3 proveedores**: `GroqProvider`, `HuggingFaceProvider`, `OpenAIProvider`
- **Interfaz común**: `AIProviderInterface` con métodos estandarizados
- **Gestor central**: `AIContentGenerator` que orquesta los proveedores
- **Configuración flexible**: 3 niveles (env vars, config.local.php, DB)

---

## 🏗️ Diseño de Implementación

### Opción 1: Nueva Clase `GitHubModelsProvider` (RECOMENDADO)

**Ventajas**:
- ✅ Separación clara de responsabilidades
- ✅ Permite diferente configuración de costos (GitHub Models tiene pricing diferente)
- ✅ Facilita tracking de uso específico de GitHub Models
- ✅ No afecta la implementación actual de `OpenAIProvider`

**Desventajas**:
- ⚠️ Código duplicado con `OpenAIProvider` (minimizable con trait/clase base)
- ⚠️ Un proveedor adicional a mantener

### Opción 2: Modificar `OpenAIProvider` Existente

**Ventajas**:
- ✅ Sin código duplicado
- ✅ Un solo proveedor para APIs compatibles con OpenAI

**Desventajas**:
- ❌ Mezcla conceptual de autenticación (OpenAI API Key vs GitHub PAT)
- ❌ Complejidad en lógica de configuración
- ❌ Dificulta tracking separado de costos/uso

---

## 💻 Implementación Propuesta: GitHubModelsProvider

### Archivo: `admin/classes/AIProviders.php`

```php
/**
 * Proveedor GitHub Models (Azure AI) para generación de contenido
 * Usa la infraestructura de Azure OpenAI con autenticación GitHub PAT
 */
class GitHubModelsProvider implements AIProviderInterface {
    private $githubToken;
    private $baseUrl = 'https://models.inference.ai.azure.com/chat/completions';
    
    // Modelos disponibles en GitHub Models (marzo 2026)
    private $models = [
        'gpt-4o-mini' => [
            'name' => 'GPT-4o Mini (GitHub)', 
            'max_tokens' => 128000, 
            'cost_per_1k' => 0.0  // Gratis en rate limit o según plan GitHub
        ],
        'gpt-4o' => [
            'name' => 'GPT-4o (GitHub)', 
            'max_tokens' => 128000, 
            'cost_per_1k' => 0.0
        ],
        'meta-llama-3.1-405b-instruct' => [
            'name' => 'Llama 3.1 405B (GitHub)', 
            'max_tokens' => 128000, 
            'cost_per_1k' => 0.0
        ],
        'mistral-large-2407' => [
            'name' => 'Mistral Large (GitHub)', 
            'max_tokens' => 128000, 
            'cost_per_1k' => 0.0
        ]
    ];
    
    private $defaultModel = 'gpt-4o-mini';
    
    public function __construct() {
        $this->githubToken = $this->getGitHubToken();
    }
    
    /**
     * Obtener GitHub Personal Access Token desde configuración
     * Scope requerido: models:read
     */
    private function getGitHubToken() {
        // Primero: Variable de entorno
        if (getenv('GITHUB_TOKEN')) {
            return getenv('GITHUB_TOKEN');
        }
        
        // Segundo: Archivo config.local.php
        try {
            $configFile = __DIR__ . '/../config/config.local.php';
            if (file_exists($configFile)) {
                require_once $configFile;
                $aiConfig = get_ai_config();
                if (!empty($aiConfig['api_keys']['github_models'])) {
                    return $aiConfig['api_keys']['github_models'];
                }
            }
        } catch (Exception $e) {
            error_log("Error loading GitHub Token from config: " . $e->getMessage());
        }
        
        // Tercero: Base de datos
        try {
            $db = Database::getInstance();
            $config = $db->fetchOne("SELECT config_value FROM system_config WHERE config_key = 'github_models_token'");
            if ($config && $config['config_value']) {
                return $config['config_value'];
            }
        } catch (Exception $e) {
            error_log("Error loading GitHub Token from DB: " . $e->getMessage());
        }
        
        return '';
    }
    
    public function generate($prompt, $options = []) {
        if (empty($this->githubToken)) {
            throw new Exception('GitHub Token no configurado (requiere scope models:read)');
        }
        
        $model = $options['model'] ?? $this->defaultModel;
        $maxTokens = $options['max_tokens'] ?? 1000;
        $temperature = $options['temperature'] ?? 0.7;
        $systemMessage = $options['system'] ?? null;
        
        // Construir mensajes según formato OpenAI
        $messages = [];
        if ($systemMessage) {
            $messages[] = [
                'role' => 'system',
                'content' => $systemMessage
            ];
        }
        $messages[] = [
            'role' => 'user',
            'content' => $prompt
        ];
        
        $data = [
            'model' => $model,
            'messages' => $messages,
            'max_tokens' => $maxTokens,
            'temperature' => $temperature
        ];
        
        $response = $this->makeRequest($data);
        
        if (!$response) {
            throw new Exception('No se recibió respuesta de GitHub Models');
        }
        
        $content = $response['choices'][0]['message']['content'] ?? '';
        $tokensUsed = $response['usage']['total_tokens'] ?? 0;
        $costEstimated = $this->calculateCost($tokensUsed, $model);
        
        return [
            'content' => trim($content),
            'model' => $model . ' (GitHub)',
            'tokens_used' => $tokensUsed,
            'cost_estimated' => $costEstimated
        ];
    }
    
    private function makeRequest($data) {
        $headers = [
            'Authorization: Bearer ' . $this->githubToken,
            'Content-Type: application/json'
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->baseUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_SSL_VERIFYPEER => true
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("Error cURL: {$error}");
        }
        
        if ($httpCode !== 200) {
            $errorData = json_decode($response, true);
            $errorMsg = $errorData['error']['message'] ?? "HTTP Error {$httpCode}";
            throw new Exception("Error GitHub Models API: {$errorMsg}");
        }
        
        return json_decode($response, true);
    }
    
    private function calculateCost($tokens, $model) {
        // GitHub Models es generalmente gratis dentro de rate limits
        // Si se exceden límites, revisar documentación de pricing
        if (!isset($this->models[$model])) {
            return 0;
        }
        
        $costPer1k = $this->models[$model]['cost_per_1k'];
        return ($tokens / 1000) * $costPer1k;
    }
    
    public function getDisplayName() {
        return 'GitHub Models';
    }
    
    public function getAvailableModels() {
        return $this->models;
    }
    
    public function getCostPerToken() {
        return $this->models[$this->defaultModel]['cost_per_1k'] / 1000;
    }
    
    public function getMaxTokens() {
        return $this->models[$this->defaultModel]['max_tokens'];
    }
    
    public function isAvailable() {
        return !empty($this->githubToken) && function_exists('curl_init');
    }
}
```

---

## ⚙️ Cambios en Configuración

### 1. Actualizar `config.local.example.php`

```php
/**
 * Configuración de IA/API (Opcional)
 */
function get_ai_config() {
    return [
        'enabled' => false,
        'default_provider' => 'groq',  // Opciones: 'groq', 'huggingface', 'openai', 'github_models'
        
        // API Keys
        'api_keys' => [
            'groq' => '',
            'huggingface' => '',
            'openai' => '',
            'github_models' => ''  // ⚠️ NUEVO: GitHub Personal Access Token (scope: models:read)
        ],
        
        // Configuración específica de GitHub Models
        'github_models' => [
            'enabled' => false,
            'preferred_model' => 'gpt-4o-mini',  // 'gpt-4o-mini', 'gpt-4o', 'meta-llama-3.1-405b-instruct'
            'rate_limit_info' => 'Ver https://docs.github.com/en/github-models/usage-limits'
        ]
    ];
}
```

### 2. Actualizar `AIContentGenerator.php`

```php
private function initializeProviders() {
    $this->providers = [
        'groq' => new GroqProvider(),
        'huggingface' => new HuggingFaceProvider(),
        'openai' => new OpenAIProvider(),
        'github_models' => new GitHubModelsProvider()  // ⚠️ NUEVO
    ];
}
```

### 3. Actualizar UI Admin (Dropdown de Proveedores)

**Archivos a modificar**:
- `admin/pages/article-create.php`
- `admin/modules/ai/ai-generator-form.php` (si existe)

```html
<select name="ai_provider" id="ai_provider" class="form-select">
    <option value="groq">Groq (Llama/Mixtral)</option>
    <option value="huggingface">Hugging Face</option>
    <option value="openai">OpenAI Directo</option>
    <option value="github_models">GitHub Models (GPT-4o)</option> <!-- NUEVO -->
</select>
```

---

## 🔒 Configuración del GitHub Token

### Paso 1: Generar Personal Access Token (PAT)

1. Ir a: https://github.com/settings/tokens
2. Click en **"Generate new token (classic)"** o usar **Fine-grained tokens**
3. Scopes requeridos:
   - ✅ `repo` (si es token classic)
   - ✅ **`models:read`** (scope específico para GitHub Models)
4. Copiar el token generado

### Paso 2: Configurar en el Sistema

**Opción A: Variable de Entorno** (Recomendado para producción)
```bash
# En .env o configuración del servidor
GITHUB_TOKEN=github_pat_XXX...
```

**Opción B: `config.local.php`** (Desarrollo)
```php
'api_keys' => [
    'github_models' => 'github_pat_XXX...'
]
```

**Opción C: Base de Datos** (Admin UI)
```sql
INSERT INTO system_config (config_key, config_value, config_type) 
VALUES ('github_models_token', 'github_pat_XXX...', 'string');
```

---

## 📊 Comparación de Proveedores

| Feature | OpenAI Directo | GitHub Models | Groq | HuggingFace |
|---------|---------------|---------------|------|-------------|
| **Autenticación** | API Key OpenAI | GitHub PAT | API Key Groq | API Key HF |
| **Costo** | Pago por uso | Gratis* | Gratis* | Gratis/Limitado |
| **Modelos GPT-4** | ✅ | ✅ (4o, 4o-mini) | ❌ | ❌ |
| **Rate Limits** | Alto (depende plan) | Moderado | Alto | Variable |
| **Latencia** | Baja | Media (Azure) | Muy baja | Alta |
| **Producción** | ✅ Sí | ⚠️ Limitado | ✅ Sí | ⚠️ Limitado |
| **Ventaja Principal** | Oficial, estable | **Sin tarjeta, GPT-4** | Velocidad | Variedad modelos |

*Dentro de límites de uso gratuito

---

## ⚠️ Consideraciones y Limitaciones

### Rate Limits de GitHub Models
Según documentación GitHub (verificar actualizaciones):
- **Free tier**: ~15 requests/minuto, ~150 requests/día
- **Pro/Team**: Límites más altos
- **Tokens/request**: Variable según modelo

### Casos de Uso Recomendados

✅ **Ideal para**:
- Prototipado y desarrollo
- Sitios con tráfico bajo-medio
- Testing de modelos GPT-4 sin compromiso de pago
- Blogs personales o portfolios

❌ **NO recomendado para**:
- Aplicaciones de alto tráfico
- Servicios críticos de producción (usar OpenAI directo)
- Escenarios donde exceder límites es común

### Estrategia de Fallback

```php
// En AIContentGenerator.php
public function generateContent($prompt, $type = 'article', $provider = null, $options = []) {
    $provider = $provider ?: $this->defaultProvider;
    
    try {
        return $this->providers[$provider]->generate(...);
    } catch (Exception $e) {
        // Si GitHub Models falla (rate limit), intentar con Groq
        if ($provider === 'github_models') {
            error_log("GitHub Models fallback to Groq: " . $e->getMessage());
            return $this->providers['groq']->generate(...);
        }
        throw $e;
    }
}
```

---

## 🚀 Plan de Implementación

### Fase 1: Implementación Base (1-2h)
- [x] Crear clase `GitHubModelsProvider` en `AIProviders.php`
- [x] Actualizar `config.local.example.php` con nueva configuración
- [x] Registrar proveedor en `AIContentGenerator::initializeProviders()`
- [x] Actualizar documentación en `.github/copilot-instructions.md`

### Fase 2: Integración UI (1h)
- [ ] Añadir opción "GitHub Models" en dropdowns admin
- [ ] Actualizar modals/forms de generación AI
- [ ] Añadir tooltip explicativo sobre límites

### Fase 3: Testing (1h)
- [ ] Test de autenticación con GitHub PAT
- [ ] Test con modelos: `gpt-4o-mini`, `gpt-4o`
- [ ] Verificar logging en `ai_usage_logs` tabla
- [ ] Test de fallback a Groq si falla

### Fase 4: Documentación (30min)
- [ ] Actualizar README con instrucciones GitHub Token
- [ ] Documentar rate limits en admin UI
- [ ] Añadir ejemplos de uso en doc/

---

## 💡 Recomendaciones Adicionales

### 1. Monitoreo de Uso
Añadir en admin dashboard:
```php
// Mostrar uso de GitHub Models API
$githubUsage = $db->fetchOne("
    SELECT COUNT(*) as requests, SUM(tokens_used) as total_tokens 
    FROM ai_usage_logs 
    WHERE provider = 'github_models' 
    AND DATE(created_at) = CURDATE()
");
```

### 2. Cache de Respuestas
Para optimizar límites de rate:
```php
// Cachear prompts comunes
$cacheKey = md5($prompt . $model);
if ($cached = $this->getCache($cacheKey)) {
    return $cached;
}
```

### 3. Alternativa: GitHub Codespaces
Si tu aplicación corre en Codespaces, GitHub Models puede tener límites más generosos.

---

## 📚 Referencias

- **GitHub Models Docs**: https://docs.github.com/en/github-models
- **Azure OpenAI API**: https://learn.microsoft.com/en-us/azure/ai-services/openai/reference
- **OpenAI PHP Client**: Tu implementación actual en `AIProviders.php`
- **Rate Limits**: https://docs.github.com/en/github-models/usage-limits

---

## ✅ Conclusión

**La implementación de GitHub Models es viable y recomendada para**:
- ✅ Tu portfolio personal (tráfico bajo-medio)
- ✅ Testing de modelos GPT-4 sin costo inicial
- ✅ Desarrollo sin necesidad de configurar facturación OpenAI

**Arquitectura propuesta**:
- Nueva clase `GitHubModelsProvider` (separada de `OpenAIProvider`)
- Autenticación con GitHub PAT (scope `models:read`)
- Fallback automático a Groq si se exceden límites
- Configuración compatible con tu sistema actual de 3 niveles

**Esfuerzo de implementación**: ~3-4 horas total

**¿Proceder con implementación?** 🚀
