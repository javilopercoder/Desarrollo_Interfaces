package Tarea_Javascript_I.Vehiculos.Vehiculos_java;

public class Turismo extends Vehiculo {
    private int numeroPlazas;

    // Constructor
    public Turismo(String matricula, String marca, String modelo, String color, int numeroPuertas, int potencia, int numeroPlazas) {
        super(matricula, marca, modelo, color, numeroPuertas, potencia);
        this.numeroPlazas = numeroPlazas;
    }

    // Getter y Setter
    public int getNumeroPlazas() {
        return numeroPlazas;
    }

    public void setNumeroPlazas(int numeroPlazas) {
        this.numeroPlazas = numeroPlazas;
    }

    @Override
    public String imprimirDatos() {
        return super.imprimirDatos() + ", Plazas: " + numeroPlazas;
    }
}
