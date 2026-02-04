/**
 * Configuración personalizada para react-app-rewired
 * Quita los hashes de los archivos del build para tener nombres fijos
 */

module.exports = function override(config, env) {
  // Solo aplicar en builds de producción
  if (env === 'production') {
    // Configurar nombres de archivos JS sin hash
    config.output.filename = 'static/js/[name].js';
    config.output.chunkFilename = 'static/js/[name].chunk.js';

    // Configurar CSS sin hash
    const miniCssExtractPlugin = config.plugins.find(
      plugin => plugin.constructor.name === 'MiniCssExtractPlugin'
    );
    if (miniCssExtractPlugin) {
      miniCssExtractPlugin.options.filename = 'static/css/[name].css';
      miniCssExtractPlugin.options.chunkFilename = 'static/css/[name].chunk.css';
    }

    // Configurar assets (imágenes, fuentes, etc.) sin hash
    config.module.rules.forEach(rule => {
      if (rule.oneOf) {
        rule.oneOf.forEach(oneOfRule => {
          // Para archivos de tipo asset/resource (imágenes, fuentes, etc.)
          if (oneOfRule.type === 'asset/resource') {
            oneOfRule.generator = {
              filename: 'static/media/[name][ext]'
            };
          }
          // Para archivos asset (que pueden ser inline o resource)
          if (oneOfRule.type === 'asset') {
            oneOfRule.generator = {
              filename: 'static/media/[name][ext]'
            };
          }
        });
      }
    });

    // Remover hash de WebpackManifestPlugin
    const manifestPlugin = config.plugins.find(
      plugin => plugin.constructor.name === 'WebpackManifestPlugin'
    );
    if (manifestPlugin) {
      manifestPlugin.options.generate = (seed, files, entries) => {
        const manifestFiles = files.reduce((manifest, file) => {
          manifest[file.name] = file.path;
          return manifest;
        }, seed);
        return manifestFiles;
      };
    }
  }

  return config;
};
