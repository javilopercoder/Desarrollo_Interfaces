package Tarea_Usabilidad.Tarea2Vehiculo;

import java.util.ArrayList;
import java.util.List;

public class Main {
    public static void main(String[] args) {
        // Crear una lista de vehículos
        List<Vehiculo> vehiculos = new ArrayList<>();

        // Agregar vehículos a la lista
        vehiculos.add(new Turismo("1234ABC", "Toyota", "Corolla", "Rojo", 4, 120, 5));
        vehiculos.add(new Turismo("5678DEF", "Honda", "Civic", "Azul", 4, 140, 5));
        vehiculos.add(new Deportivo("9012GHI", "Ferrari", "Spider", "Negro", 2, 500, true));
        vehiculos.add(new Deportivo("3456JKL", "Porsche", "911", "Blanco", 2, 450, false));

        // Recorrer y mostrar la información de todos los vehículos
        System.out.println("Información de los vehículos registrados:");
        for (Vehiculo vehiculo : vehiculos) {
            vehiculo.imprimirDatos();
            System.out.println(); // Salto de línea
        }
    }
}