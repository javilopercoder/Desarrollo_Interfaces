package Tarea_Usabilidad.Tarea2Vehiculo;

// Clase Turismo (subclase de Vehículo)
class Turismo extends Vehiculo{
    private int numeroPlazas;

    // Constructor
    public Turismo(String matricula, String marca, String modelo, String color, int numeroPuertas, double potencia, int numeroPlazas) {
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

    // Método para imprimir datos específicos de Turismo
    @Override
    public void imprimirDatos() {
        super.imprimirDatos();
        System.out.println("Número de plazas: " + numeroPlazas);
    }
}
