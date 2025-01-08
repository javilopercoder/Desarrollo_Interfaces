package Tarea_Javascript_I.Vehiculos.Vehiculos_java;

import java.util.ArrayList;
import java.util.List;

public class Main {
    public static void main(String[] args) {
        // Crear una lista de vehículos
        List<Vehiculo> vehiculos = new ArrayList<>();

        // Añadir vehículos a la lista
        vehiculos.add(new Turismo("1234ABC", "Toyota", "Corolla", "Blanco", 4, 110, 5));
        vehiculos.add(new Deportivo("5678DEF", "Ferrari", "F8", "Rojo", 2, 720, true));
        vehiculos.add(new Turismo("9012GHI", "Renault", "Clio", "Negro", 4, 90, 5));
        vehiculos.add(new Deportivo("3456JKL", "Porsche", "911", "Amarillo", 2, 450, false));

        // Mostrar los datos de cada vehículo
        System.out.println("Datos de los vehículos registrados:");
        for (Vehiculo vehiculo : vehiculos) {
            System.out.println(vehiculo.imprimirDatos());
        }
    }
}
