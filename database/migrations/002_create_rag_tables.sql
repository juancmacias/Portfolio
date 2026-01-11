-- Migración de Base de Datos para Sistema RAG Chat
-- Fecha: 6 de noviembre de 2025
-- Versión: 1.0

-- Tabla para gestión de prompts personalizables
CREATE TABLE IF NOT EXISTS chat_prompts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    prompt_name VARCHAR(100) UNIQUE NOT NULL,
    prompt_type ENUM('system', 'context', 'personality', 'response') NOT NULL,
    prompt_text LONGTEXT NOT NULL,
    variables JSON, -- Variables dinámicas: {name}, {portfolio_info}, etc.
    is_active BOOLEAN DEFAULT TRUE,
    priority INT DEFAULT 0,
    created_by VARCHAR(100) DEFAULT 'admin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_prompt_type (prompt_type),
    INDEX idx_is_active (is_active),
    INDEX idx_priority (priority)
);

-- Tabla para documentos de referencia subidos por admin
CREATE TABLE IF NOT EXISTS reference_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    document_name VARCHAR(255) NOT NULL,
    document_type ENUM('pdf', 'txt', 'docx', 'md', 'url') NOT NULL,
    file_path VARCHAR(500),
    original_filename VARCHAR(255),
    file_size INT DEFAULT 0,
    content_extracted LONGTEXT,
    content_summary TEXT,
    metadata JSON,
    tags JSON,
    is_active BOOLEAN DEFAULT TRUE,
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_processed TIMESTAMP NULL,
    processing_status ENUM('pending', 'processing', 'completed', 'error') DEFAULT 'pending',
    
    INDEX idx_document_type (document_type),
    INDEX idx_is_active (is_active),
    INDEX idx_processing_status (processing_status),
    FULLTEXT INDEX idx_content_search (content_extracted, content_summary)
);

-- Tabla para fragmentos de documentos (chunks) para RAG
CREATE TABLE IF NOT EXISTS document_chunks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    document_id INT NOT NULL,
    chunk_index INT NOT NULL,
    chunk_text LONGTEXT NOT NULL,
    chunk_summary TEXT,
    chunk_keywords JSON,
    relevance_score FLOAT DEFAULT 0.0,
    word_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (document_id) REFERENCES reference_documents(id) ON DELETE CASCADE,
    INDEX idx_document_id (document_id),
    INDEX idx_chunk_index (chunk_index),
    INDEX idx_relevance_score (relevance_score),
    FULLTEXT INDEX idx_chunk_search (chunk_text, chunk_summary)
);

-- Tabla para conversaciones mejoradas con contexto de documentos
CREATE TABLE IF NOT EXISTS enhanced_conversations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(100) NOT NULL,
    message_id VARCHAR(100) UNIQUE NOT NULL,
    user_message LONGTEXT,
    bot_response LONGTEXT,
    prompt_used_id INT NULL,
    documents_referenced JSON, -- IDs de documentos usados
    chunks_used JSON, -- IDs de chunks específicos
    llm_provider VARCHAR(50) DEFAULT 'groq',
    llm_model VARCHAR(100) DEFAULT 'llama-3',
    processing_time_ms INT DEFAULT 0,
    user_feedback ENUM('positive', 'negative', 'neutral') NULL,
    admin_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (prompt_used_id) REFERENCES chat_prompts(id) ON DELETE SET NULL,
    INDEX idx_session_id (session_id),
    INDEX idx_message_id (message_id),
    INDEX idx_llm_provider (llm_provider),
    INDEX idx_user_feedback (user_feedback),
    INDEX idx_created_at (created_at)
);

-- Tabla para embeddings simplificados (sin vectores complejos)
CREATE TABLE IF NOT EXISTS simple_embeddings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    content_id VARCHAR(100) UNIQUE NOT NULL,
    content_type ENUM('portfolio', 'document', 'chunk', 'project') NOT NULL,
    content_text LONGTEXT NOT NULL,
    keywords JSON,
    tf_idf_vector JSON, -- Vector TF-IDF simple para similaridad
    related_content JSON, -- IDs de contenido relacionado
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_content_type (content_type),
    FULLTEXT INDEX idx_content_text (content_text)
);

-- Tabla para configuración del sistema de chat
CREATE TABLE IF NOT EXISTS chat_configuration (
    id INT AUTO_INCREMENT PRIMARY KEY,
    config_key VARCHAR(100) UNIQUE NOT NULL,
    config_value LONGTEXT,
    config_type ENUM('string', 'json', 'boolean', 'integer', 'float') DEFAULT 'string',
    description TEXT,
    is_editable BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_config_key (config_key)
);

-- Insertar prompts iniciales del sistema
INSERT INTO chat_prompts (prompt_name, prompt_type, prompt_text, variables, priority) VALUES
('personality_base', 'system', 'Eres un asistente virtual que representa a Juan Carlos Macías, un desarrollador full stack e especialista en IA/MLOps de Madrid, España. Tu objetivo es ayudar a los visitantes a conocer mejor su perfil profesional, proyectos y habilidades. Responde de manera profesional pero cercana, en español, y siempre basándote en la información real proporcionada.', '["name", "location", "profession"]', 10),

('context_integration', 'context', 'Utiliza la siguiente información del portfolio de Juan Carlos para responder: {portfolio_data}\n\nDocumentación de referencia: {document_chunks}\n\nInformación de proyectos: {project_info}\n\nBasándote en esta información, responde de manera precisa y útil.', '["portfolio_data", "document_chunks", "project_info"]', 8),

('response_structure', 'response', 'Estructura tu respuesta de la siguiente manera:\n1. Responde directamente a la pregunta\n2. Proporciona detalles relevantes basados en la información disponible\n3. Si es apropiado, menciona proyectos o experiencias específicas\n4. Termina con una pregunta de seguimiento para mantener la conversación activa\n\nMantén un tono profesional pero accesible, y respuestas entre 100-300 palabras.', '["user_query"]', 6),

('technical_expertise', 'personality', 'Cuando hables sobre tecnologías o proyectos técnicos, demuestra el conocimiento profundo de Juan Carlos en:\n- Desarrollo Full Stack (React, PHP, Python, JavaScript)\n- Inteligencia Artificial y Machine Learning\n- Base de datos (MySQL, PostgreSQL, MongoDB)\n- DevOps y sistemas (Docker, Linux)\n- Experiencia de más de 15 años en tecnología\n\nSé específico con ejemplos reales de sus proyectos cuando sea relevante.', '["technologies", "projects"]', 7),

('helpful_guidance', 'personality', 'Sé proactivo en ofrecer información útil:\n- Si preguntan sobre experiencia, menciona proyectos relevantes\n- Si preguntan sobre contacto, proporciona formas de conectar\n- Si preguntan sobre habilidades, da ejemplos concretos\n- Si preguntan sobre disponibilidad, menciona que está en búsqueda activa\n- Sugiere descargar el CV o ver proyectos específicos cuando sea apropiado', '["contact_info", "availability"]', 5);

-- Insertar configuración inicial del sistema
INSERT INTO chat_configuration (config_key, config_value, config_type, description) VALUES
('default_llm_provider', 'groq', 'string', 'Proveedor de IA por defecto (groq o huggingface)'),
('default_llm_model', 'llama-3-8b-8192', 'string', 'Modelo de IA por defecto para respuestas'),
('max_response_tokens', '800', 'integer', 'Máximo número de tokens en respuestas'),
('temperature', '0.7', 'float', 'Temperatura para generación de respuestas (0.0-1.0)'),
('max_context_chunks', '5', 'integer', 'Máximo número de chunks de documentos en contexto'),
('session_timeout_minutes', '30', 'integer', 'Tiempo de expiración de sesión en minutos'),
('enable_voice', 'true', 'boolean', 'Habilitar funcionalidades de voz'),
('enable_rag', 'true', 'boolean', 'Habilitar búsqueda RAG en documentos'),
('response_language', 'es', 'string', 'Idioma de respuestas por defecto');

-- Insertar embeddings iniciales del portfolio
INSERT INTO simple_embeddings (content_id, content_type, content_text, keywords) VALUES
('portfolio_about', 'portfolio', 'Juan Carlos Macías Salvador es un desarrollador full stack e especialista en inteligencia artificial y MLOps con sede en Madrid, España. Tiene más de 15 años de experiencia combinando desarrollo web y móvil con machine learning, automatización y ciencia de datos. Su enfoque se centra en crear soluciones que mejoren la vida de las personas, especialmente en sectores como salud, educación y electrónica.', '["Juan Carlos Macías", "desarrollador full stack", "IA", "MLOps", "Madrid", "machine learning", "salud", "educación"]'),

('portfolio_skills', 'portfolio', 'Habilidades técnicas: Python (FastAPI, Flask, Pandas, Scikit-learn), JavaScript, React, Node.js, PHP, Java, SQL, PostgreSQL, MySQL, MongoDB, Docker, GitHub, DevOps, LLMs, web scraping, UX/UI, sistemas Linux. Experiencia en desarrollo frontend con React.js, Next.js y JavaScript moderno, además del desarrollo nativo en Java para Android.', '["Python", "React", "JavaScript", "PHP", "Java", "SQL", "Docker", "DevOps", "machine learning"]'),

('portfolio_experience', 'portfolio', 'Experiencia profesional: Más de 15 años en electrónica en empresas como Fokus Reparaciones, Fnac y MediaMarkt. Trabajando actualmente como freelance en proyectos personales. Ha completado un bootcamp intensivo en Inteligencia Artificial (Factoría F5, UE, 2025), junto a certificaciones en ciberseguridad, desarrollo web full stack y programación orientada a objetos.', '["freelance", "Factoría F5", "bootcamp IA", "Fnac", "MediaMarkt", "15 años experiencia"]'),

('portfolio_projects', 'portfolio', 'Proyectos destacados: Konglu.es (aplicación de seguimiento glucémico y deportivo personalizada desarrollada con equipo médico), CEIP Barcelona Madrid (página web corporativa con PHP y MySQL), Portfolio JCMS (desarrollado con React), Thinking With You (experiencia inmersiva en Vercel), donde-reparar.com (servicio técnico con gestión de clientes).', '["Konglu.es", "CEIP Barcelona", "Portfolio JCMS", "Thinking With You", "donde-reparar.com", "React", "PHP", "MySQL"]'),

('portfolio_education', 'portfolio', 'Formación: Bootcamp intensivo en Inteligencia Artificial (Factoría F5, UE, 2025), certificaciones en ciberseguridad, desarrollo web full stack y programación orientada a objetos. Participación en iniciativas como We The Humans y aceleradora La Nave, centradas en desarrollo ético de IA según normativa europea.', '["Factoría F5", "IA", "ciberseguridad", "full stack", "We The Humans", "La Nave", "IA ética"]'),

('portfolio_contact', 'portfolio', 'Información de contacto: Juan Carlos Macías Salvador, Email: juancmaciassalvador@gmail.com, Teléfono: +34 618309775, LinkedIn: https://linkedin.com/in/juancmacias, GitHub: https://github.com/juancmacias, Ubicación: Madrid, España. Actualmente en búsqueda activa de empleo.', '["contacto", "email", "teléfono", "LinkedIn", "GitHub", "Madrid", "búsqueda activa"]');

-- Crear índices adicionales para optimización
CREATE INDEX idx_embeddings_keywords ON simple_embeddings((CAST(keywords AS CHAR(1000))));
CREATE INDEX idx_conversations_session_created ON enhanced_conversations(session_id, created_at);
CREATE INDEX idx_documents_active_type ON reference_documents(is_active, document_type);