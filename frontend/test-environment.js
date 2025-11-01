// Test Environment Detection
// Execute this in browser console to test environment detection

// Simular diferentes hostnames
const originalLocation = window.location;

const testEnvironments = [
  'localhost',
  '127.0.0.1',
  'perfil.in',
  'juancarlosmacias.es',
  'test.local',
  'mydomain.test'
];

console.log('🧪 Testing Environment Detection:');
console.log('================================');

testEnvironments.forEach(hostname => {
  // Mock window.location.hostname
  delete window.location;
  window.location = { hostname: hostname };
  
  // Re-import the URL detection logic
  const isLocal = () => {
    const hostname = window.location.hostname;
    return hostname === 'localhost' || 
           hostname === '127.0.0.1' || 
           hostname === 'perfil.in' ||
           hostname.includes('.local') ||
           hostname.includes('.test') ||
           process.env.NODE_ENV === 'development';
  };

  const API_URLS = {
    local: 'http://perfil.in/',
    production: 'https://www.juancarlosmacias.es/'
  };

  const urlApi = isLocal() ? API_URLS.local : API_URLS.production;
  
  console.log(`${hostname.padEnd(20)} → ${isLocal() ? 'LOCAL' : 'PROD'} → ${urlApi}`);
});

// Restore original location
window.location = originalLocation;
console.log('================================');
console.log('✅ Environment detection test completed!');