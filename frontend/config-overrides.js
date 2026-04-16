/**
 * Configuración personalizada para react-app-rewired
 * Quita los hashes de los archivos del build para tener nombres fijos
 * + Optimizaciones de webpack para reducir tamaño del bundle
 */

const webpack = require('webpack');

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

    // ========== OPTIMIZACIONES DE TAMAÑO ==========

    // 1. Desactivar source maps (reduce 13MB en producción)
    config.devtool = false;

    // 2. Configurar Terser para eliminar console.* en producción
    const TerserPlugin = require('terser-webpack-plugin');
    
    config.optimization = {
      ...config.optimization,
      minimize: true,
      minimizer: [
        new TerserPlugin({
          terserOptions: {
            compress: {
              // Eliminar todos los console.* (log, warn, error, info, debug, etc.)
              drop_console: true,
              // Eliminar también debugger statements
              drop_debugger: true,
              // Optimizaciones adicionales
              pure_funcs: ['console.log', 'console.info', 'console.debug'],
            },
            format: {
              // Eliminar comentarios
              comments: false,
            },
          },
          // Extraer comentarios de licencia a archivos separados
          extractComments: false,
        }),
      ],
    };
  }

  return config;
};
