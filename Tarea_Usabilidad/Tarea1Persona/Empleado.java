package Tarea_Usabilidad.Tarea1Persona; // Asegúrate de que el paquete sea correcto

class Empleado extends Persona {
    private double sueldo;
    private double comision;

    // Constructores
    public Empleado() {
        super();
    }

    public Empleado(String nombre, String apellidos, int edad, double sueldo, double comision) {
        super(nombre, apellidos, edad);
        if (sueldo > 0 && comision > 0) {
            this.sueldo = sueldo;
            this.comision = comision;
        } else {
            throw new IllegalArgumentException("El sueldo y la comisión deben ser mayores a cero.");
        }
    }

    // Getters y Setters
    public double getSueldo() {
        return sueldo;
    }

    public void setSueldo(double sueldo) {
        if (sueldo > 0) {
            this.sueldo = sueldo;
        } else {
            System.out.println("El sueldo debe ser mayor a cero.");
        }
    }

    public double getComision() {
        return comision;
    }

    public void setComision(double comision) {
        if (comision > 0) {
            this.comision = comision;
        } else {
            System.out.println("La comisión debe ser mayor a cero.");
        }
    }

    // Método para calcular el total cobrado
    public double calcularTotalCobrado() {
        return sueldo + comision;
    }

    // Método para imprimir el sueldo y la comisión
    public void imprimirSueldoYComision() {
        System.out.println("Sueldo: " + sueldo + ", Comisión: " + comision + ", Total cobrado: " + calcularTotalCobrado());
    }
}