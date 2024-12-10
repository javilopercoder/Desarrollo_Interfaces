package Tarea_Usabilidad.Tarea2Vehiculo;

// Clase Deportivo (subclase de Vehículo)
class Deportivo extends Vehiculo {
    private boolean esDescapotable;

    // Constructor
    public Deportivo(String matricula, String marca, String modelo, String color, int numeroPuertas, double potencia, boolean esDescapotable) {
        super(matricula, marca, modelo, color, numeroPuertas, potencia);
        this.esDescapotable = esDescapotable;
    }

    // Getter y Setter
    public boolean isEsDescapotable() {
        return esDescapotable;
    }

    public void setEsDescapotable(boolean esDescapotable) {
        this.esDescapotable = esDescapotable;
    }

    // Método para imprimir datos específicos de Deportivo
    @Override
    public void imprimirDatos() {
        super.imprimirDatos();
        System.out.println("Descapotable: " + (esDescapotable ? "Sí" : "No"));
    }
}