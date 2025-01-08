package Tarea_Javascript_I.Vehiculos.Vehiculos_java;

public class Vehiculo {
    private String matricula;
    private String marca;
    private String modelo;
    private String color;
    private int numeroPuertas;
    private int potencia;

    // Constructor
    public Vehiculo(String matricula, String marca, String modelo, String color, int numeroPuertas, int potencia) {
        this.matricula = matricula;
        this.marca = marca;
        this.modelo = modelo;
        this.color = color;
        this.numeroPuertas = numeroPuertas;
        this.potencia = potencia;
    }

    // Getters y Setters
    public String getMatricula() {
        return matricula;
    }

    public void setMatricula(String matricula) {
        this.matricula = matricula;
    }

    public String getMarca() {
        return marca;
    }

    public void setMarca(String marca) {
        this.marca = marca;
    }

    public String getModelo() {
        return modelo;
    }

    public void setModelo(String modelo) {
        this.modelo = modelo;
    }

    public String getColor() {
        return color;
    }

    public void setColor(String color) {
        this.color = color;
    }

    public int getNumeroPuertas() {
        return numeroPuertas;
    }

    public void setNumeroPuertas(int numeroPuertas) {
        this.numeroPuertas = numeroPuertas;
    }

    public int getPotencia() {
        return potencia;
    }

    public void setPotencia(int potencia) {
        this.potencia = potencia;
    }

    // Método para mostrar los datos del vehículo
    public String imprimirDatos() {
        return "Matrícula: " + matricula + ", Marca: " + marca + ", Modelo: " + modelo +
                ", Color: " + color + ", Puertas: " + numeroPuertas + ", Potencia: " + potencia + " CV";
    }
}
