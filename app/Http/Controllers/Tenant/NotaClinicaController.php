<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\NotaClinica;
use App\Models\Tenant\Cita;
use App\Services\Tenant\NotaCifradaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class NotaClinicaController extends Controller
{
    protected $cifradoService;

    public function __construct(NotaCifradaService $cifradoService)
    {
        $this->cifradoService = $cifradoService;
    }

    /**
     * Crear una nota clínica cifrada AES-256
     */
    public function store(Request $request)
    {
        $request->validate([
            'cita_id' => 'required|exists:agenda_citas,id',
            'cliente_id' => 'required',
            'titulo' => 'nullable|string',
            'contenido' => 'required|string'
        ]);

        // Cifrar el contenido antes de persistirlo
        $cifrado = $this->cifradoService->cifrar($request->contenido);

        $nota = NotaClinica::create([
            'cita_id' => $request->cita_id,
            'cliente_id' => $request->cliente_id,
            'medico_id' => auth()->id(), // El autor
            'titulo' => $request->titulo,
            'contenido_cifrado' => $cifrado['contenido'],
            'iv' => $cifrado['iv']
        ]);

        return response()->json([
            'success' => true,
            'nota_id' => $nota->id,
            'mensaje' => 'Nota clínica guardada y cifrada correctamente.'
        ]);
    }

    /**
     * Ver/Descifrar una nota clínica (Solo el autor)
     */
    public function show($id)
    {
        $nota = NotaClinica::findOrFail($id);
        
        // El Global Scope ya filtra por auth()->id(), pero doble validación por Policy
        \Illuminate\Support\Facades\Gate::authorize('view', $nota);

        return response()->json([
            'id' => $nota->id,
            'titulo' => $nota->titulo,
            'contenido' => $this->cifradoService->descifrar($nota->contenido_cifrado, $nota->iv), // Descifrar al vuelo
            'fecha' => $nota->created_at->format('Y-m-d H:i')
        ]);
    }
}
