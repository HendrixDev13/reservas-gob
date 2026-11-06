import { Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { environment } from '../../environments/environment';

@Injectable({ providedIn: 'root' })
export class ApiService {
  constructor(private http: HttpClient) {}
  dashboard() { return this.http.get<any>(`${environment.apiUrl}/dashboard`); }
  salones() { return this.http.get<any>(`${environment.apiUrl}/salones`); }
  equipos() { return this.http.get<any>(`${environment.apiUrl}/equipos`); }
  auditoria() { return this.http.get<any>(`${environment.apiUrl}/auditoria`); }
  reservas(from?: string, to?: string) {
    let params = new HttpParams();
    if (from) params = params.set('from', from);
    if (to) params = params.set('to', to);
    return this.http.get<any>(`${environment.apiUrl}/reservas`, { params });
  }
  crearReserva(data: any) { return this.http.post<any>(`${environment.apiUrl}/reservas`, data); }
  cambiarEstado(id: number, action: string, motivo = '') {
    return this.http.patch<any>(`${environment.apiUrl}/reservas/${id}/${action}`, { motivo });
  }
  crearSalon(data: any) { return this.http.post<any>(`${environment.apiUrl}/salones`, data); }
  crearEquipo(data: any) { return this.http.post<any>(`${environment.apiUrl}/equipos`, data); }
}
