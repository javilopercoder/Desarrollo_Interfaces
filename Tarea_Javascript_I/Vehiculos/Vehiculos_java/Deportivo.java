package Tarea_Javascript_I.Vehiculos.Vehiculos_java;

public class Deportivo extends Vehiculo {
    private boolean descapotable;

    // Constructor
    public Deportivo(String matricula, String marca, String modelo, String color, int numeroPuertas, int potencia, boolean descapotable) {
        super(matricula, marca, modelo, color, numeroPuertas, potencia);
        this.descapotable = descapotable;
    }

    // Getter y Setter
    public boolean isDescapotable() {
        return descapotable;
    }

    public void setDescapotable(boolean descapotable) {
        this.descapotable = descapotable;
    }

    @Override
    public String imprimirDatos() {
        return super.imprimirDatos() + ", Descapotable: " + (descapotable ? "Sí" : "No");
    }
}
