const express = require('express');
const cors = require('cors');
require('dotenv').config();

const app = express();
const apiRoutes = require('./routes/api');

// 1. AJUSTE DE CORS: 
// Permitimos que tu futuro link de Render (frontend) pueda consultar este backend.
app.use(cors({
    origin: '*', // Esto permite que cualquier origen se conecte (lo más seguro para evitar errores de bloqueo ahora)
    credentials: true
}));

app.use(express.json());

// 2. LA JUGADA MAESTRA (Redirección):
// Cuando el profesor abra el link del backend que le enviaste,
// lo mandaremos automáticamente a la página visual (frontend).
app.get('/', (req, res) => {
    // Reemplaza el link de abajo por el link que te dé Render en el Static Site
    res.redirect('https://yape-store-web.onrender.com'); 
});

// Usar las rutas
app.use('/api', apiRoutes);

// Iniciar servidor
const PORT = process.env.PORT || 3001;
app.listen(PORT, () => {
    console.log(`Servidor corriendo en el puerto ${PORT}`);
});
