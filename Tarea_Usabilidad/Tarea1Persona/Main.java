package Tarea_Usabilidad.Tarea1Persona;

public class Main {
    public static void main(String[] args) {
        // Crear 4 objetos de la clase Persona
        Persona p1 = new Persona("Juan", "Pérez", 30);
        Persona p2 = new Persona("Ana", "López", 25);
        Persona p3 = new Persona("Carlos", "Gómez", 40);
        Persona p4 = new Persona("María", "Hernández", 35);

        // Imprimir datos de las personas
        p1.imprimirDatos();
        p2.imprimirDatos();
        p3.imprimirDatos();
        p4.imprimirDatos();

        // Crear 5 objetos de la clase Empleado
        Empleado e1 = new Empleado("Luis", "Martínez", 28, 2000.0, 300.0);
        Empleado e2 = new Empleado("Sofía", "Ramírez", 32, 2500.0, 400.0);
        Empleado e3 = new Empleado("Andrés", "García", 45, 3000.0, 500.0);
        Empleado e4 = new Empleado("Lucía", "Fernández", 29, 2200.0, 350.0);
        Empleado e5 = new Empleado("Diego", "Castro", 38, 2800.0, 450.0);

        // Imprimir datos de los empleados
        e1.imprimirDatos();
        e1.imprimirSueldoYComision();

        e2.imprimirDatos();
        e2.imprimirSueldoYComision();

        e3.imprimirDatos();
        e3.imprimirSueldoYComision();

        e4.imprimirDatos();
        e4.imprimirSueldoYComision();

        e5.imprimirDatos();
        e5.imprimirSueldoYComision();
    }
}