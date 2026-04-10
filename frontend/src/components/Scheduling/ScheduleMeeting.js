import React, { useMemo, useState } from "react";
import { Container, Row, Col, Button, Form, Alert, Spinner } from "react-bootstrap";

const DEFAULT_RANGE_DAYS = 7;
const DEFAULT_DURATION_MIN = 30;

function formatLocalDateTime(isoString) {
  try {
    const d = new Date(isoString);
    return d.toLocaleString();
  } catch {
    return isoString;
  }
}

function ScheduleMeeting() {
  const timezone = useMemo(
    () => Intl.DateTimeFormat().resolvedOptions().timeZone || "Europe/Madrid",
    []
  );

  const [days, setDays] = useState(DEFAULT_RANGE_DAYS);
  const [durationMinutes, setDurationMinutes] = useState(DEFAULT_DURATION_MIN);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [slots, setSlots] = useState([]);
  const [booking, setBooking] = useState(false);
  const [successMsg, setSuccessMsg] = useState(null);

  const fetchAvailability = async () => {
    setLoading(true);
    setError(null);
    setSuccessMsg(null);

    try {
      const start = new Date();
      const end = new Date();
      end.setDate(end.getDate() + Number(days || DEFAULT_RANGE_DAYS));

      const resp = await fetch("/api/portfolio/calendar-availability.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          start: start.toISOString(),
          end: end.toISOString(),
          duration_minutes: Number(durationMinutes),
          timezone,
        }),
      });

      const data = await resp.json().catch(() => null);
      if (!resp.ok || !data?.success) {
        const msg = data?.error?.message || data?.error || "No se pudo obtener disponibilidad";
        throw new Error(msg);
      }

      setSlots(data.data?.slots || []);
    } catch (e) {
      setError(e.message || String(e));
      setSlots([]);
    } finally {
      setLoading(false);
    }
  };

  const bookSlot = async (slot) => {
    setBooking(true);
    setError(null);
    setSuccessMsg(null);

    try {
      const resp = await fetch("/api/portfolio/calendar-create-event.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          start: slot.start,
          end: slot.end,
          timezone,
          title: "Reunión (Portfolio)",
        }),
      });

      const data = await resp.json().catch(() => null);
      if (!resp.ok || !data?.success) {
        const msg = data?.error?.message || data?.error || "No se pudo reservar el hueco";
        throw new Error(msg);
      }

      setSuccessMsg("Reunión creada en el calendario.");
      await fetchAvailability();
    } catch (e) {
      setError(e.message || String(e));
    } finally {
      setBooking(false);
    }
  };

  return (
    <section>
      <Container fluid className="about-section container-fluid">
        <Container className="home-content">
          <h1>Agendar una reunión</h1>
          <p className="text-muted">
            Aquí podrás ver huecos disponibles y reservar una reunión. (MVP)
          </p>

          <Row className="align-items-end g-3">
            <Col md={3}>
              <Form.Label>Rango (días)</Form.Label>
              <Form.Control
                type="number"
                min={1}
                max={30}
                value={days}
                onChange={(e) => setDays(e.target.value)}
              />
            </Col>
            <Col md={3}>
              <Form.Label>Duración (min)</Form.Label>
              <Form.Control
                type="number"
                min={15}
                max={180}
                step={5}
                value={durationMinutes}
                onChange={(e) => setDurationMinutes(e.target.value)}
              />
            </Col>
            <Col md={6}>
              <div className="d-flex gap-2">
                <Button onClick={fetchAvailability} disabled={loading || booking}>
                  {loading ? (
                    <>
                      <Spinner animation="border" size="sm" className="me-2" />
                      Consultando…
                    </>
                  ) : (
                    "Ver horarios disponibles"
                  )}
                </Button>
                <Button
                  variant="outline-secondary"
                  onClick={() => {
                    setSlots([]);
                    setError(null);
                    setSuccessMsg(null);
                  }}
                  disabled={loading || booking}
                >
                  Limpiar
                </Button>
              </div>
              <div className="mt-2">
                <small className="text-muted">Zona horaria detectada: {timezone}</small>
              </div>
            </Col>
          </Row>

          {error && (
            <Alert className="mt-4" variant="danger">
              {error}
            </Alert>
          )}

          {successMsg && (
            <Alert className="mt-4" variant="success">
              {successMsg}
            </Alert>
          )}

          <div className="mt-4">
            {slots.length === 0 ? (
              <p className="text-muted">Aún no hay horarios listados.</p>
            ) : (
              <>
                <h2 className="h5">Huecos disponibles</h2>
                <div className="d-flex flex-column gap-2">
                  {slots.map((slot) => (
                    <div
                      key={`${slot.start}-${slot.end}`}
                      className="d-flex justify-content-between align-items-center p-2 rounded border"
                    >
                      <div>
                        <div>
                          <strong>{formatLocalDateTime(slot.start)}</strong>
                        </div>
                        <small className="text-muted">
                          {formatLocalDateTime(slot.end)}
                        </small>
                      </div>
                      <Button
                        variant="success"
                        onClick={() => bookSlot(slot)}
                        disabled={booking || loading}
                      >
                        {booking ? "Reservando…" : "Reservar"}
                      </Button>
                    </div>
                  ))}
                </div>
              </>
            )}
          </div>

          <hr className="my-4" />
          <p className="text-muted small mb-0">
            Nota: si no has autorizado Google Calendar todavía, el backend devolverá un error y
            añadiremos el flujo OAuth en la siguiente fase.
          </p>
        </Container>
      </Container>
    </section>
  );
}

export default ScheduleMeeting;
