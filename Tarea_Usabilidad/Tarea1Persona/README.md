# Programa de Gestión de Personas y Empleados

Este proyecto consiste en desarrollar un programa en Java que defina la clase `Persona` y la subclase `Empleado`. El programa principal debe crear objetos de tipo `Persona` y `Empleado`, y gestionar sus datos personales y laborales.

## Características

### Clase Persona
- **Atributos**:
  - Nombre
  - Apellidos
  - Edad

- **Métodos**:
  - Constructores para inicializar los atributos.
  - Getters y setters para acceder y modificar los atributos.
  - Método para imprimir los datos personales.

### Clase Empleado
- **Hereda de**: `Persona`
- **Atributos adicionales**:
  - Sueldo
  - Comisión

- **Métodos adicionales**:
  - Constructores para inicializar los atributos, asegurando que sueldo y comisión sean mayores a cero.
  - Getters y setters para acceder y modificar los atributos adicionales.
  - Método para calcular el total cobrado (sueldo + comisión).
  - Método para imprimir el sueldo, la comisión y el total cobrado.

## Funcionalidades

- Crear objetos de tipo `Persona` y `Empleado`.
- Gestionar y mostrar la información personal y laboral de cada objeto.
- Controlar que el sueldo y la comisión sean mayores a cero.
