import React from "react";

import { Container } from "react-bootstrap";

function Politicas() {
    return (
        <section>
            <Container fluid className="about-section container-fluid">
                <Container className="home-content">
                <h1>Política de Privacidad y Protección de Datos</h1>
                <p>
                    <strong>Última actualización:</strong> Junio 2025
                </p>

                <h2>1. Responsable del tratamiento</h2>
                <p>
                    <strong>Nombre:</strong> Juan Carlos Macias<br />
                    <strong>CIF/NIF:</strong> 50170588C<br />
                    <strong>Dirección:</strong> Calle de Padre Oltra<br />
                    <strong>Email:</strong> juancmaciassalvador@gmail.com
                </p>

                <h2>2. Finalidad del tratamiento de los datos</h2>
                <p>Los datos recogidos serán tratados con las siguientes finalidades:</p>
                <p>
                    - Gestión de usuarios y/o clientes<br />
                    - Prestación de los servicios solicitados<br />
                    - Mejora del servicio y análisis estadístico interno
                </p>

                <h2>3. Legitimación del tratamiento</h2>
                <p>La base legal para el tratamiento es:</p>
                <p>
                    - El consentimiento expreso del interesado<br />
                    - La ejecución de un contrato o medidas precontractuales<br />
                    - El cumplimiento de obligaciones legales
                </p>

                <h2>4. Plazo de conservación</h2>
                <p>
                    Los datos serán conservados mientras exista una relación comercial o durante el tiempo necesario para cumplir con las obligaciones legales. Una vez finalizado, se suprimirán con medidas de seguridad adecuadas.
                </p>

                <h2>5. Destinatarios</h2>
                <p>
                    No se cederán datos a terceros, salvo obligación legal o en caso necesario para la prestación del servicio.
                </p>

                <h2>6. Derechos del usuario</h2>
                <p>Puede ejercer sus derechos de:</p>
                <p>
                    - Acceso a sus datos personales<br />
                    - Rectificación de datos inexactos<br />
                    - Supresión de sus datos ("derecho al olvido")<br />
                    - Oposición al tratamiento<br />
                    - Limitación del tratamiento<br />
                    - Portabilidad de sus datos
                </p>
                <p>
                    Para ejercerlos, debe enviar una solicitud a juancmaciassalvador@gmail.com junto con copia de su DNI u otro documento acreditativo.
                </p>

                <h2>7. Medidas de seguridad</h2>
                <p>
                    El responsable aplica las medidas técnicas y organizativas adecuadas para garantizar la seguridad, confidencialidad, integridad y disponibilidad de los datos.
                </p>

                <h2>8. Política de cookies</h2>
                <p>
                    Esta web no utiliza cookies ni tecnologías de seguimiento de terceros.
                </p>

                <h2>9. Reclamaciones ante la autoridad de control</h2>
                <p>
                    Si considera que se ha vulnerado alguno de sus derechos, puede presentar una reclamación ante la Agencia Española de Protección de Datos (AEPD):{" "}
                    <a href="https://www.aepd.es" target="_blank" rel="noopener noreferrer">
                        www.aepd.es
                    </a>
                </p>
                </Container>
            </Container>
        </section>
    );
}

export default Politicas;