//** export const environment = {
  //production: //true,
  // Mantén /api solo si cPanel tiene un rewrite que enruta /api/* al backend PHP.
  // Si usas un subdominio cuyo document root es backend/public, usa la URL del subdominio sin /api.
 // apiUrl: 'https://equipo.gobernacionbajaverapaz.gob.gt/api'
//};
 /** */

 export const environment = {
  production: false,
  apiUrl: 'http://127.0.0.1:8000'
};