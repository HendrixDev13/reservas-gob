import { Injectable, signal } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { tap } from 'rxjs';
import { environment } from '../../environments/environment';

export interface User { id: number; nombre: string; correo: string; rol: 'secretaria'|'consulta'; }
interface LoginResponse { ok: boolean; data: { token: string; expires_in: number; user: User }; }

@Injectable({ providedIn: 'root' })
export class AuthService {
  readonly user = signal<User | null>(null);
  readonly token = signal<string | null>(null);

  constructor(private http: HttpClient) {}

  login(correo: string, password: string) {
    return this.http.post<LoginResponse>(`${environment.apiUrl}/auth/login`, { correo, password }).pipe(
      tap(r => { this.token.set(r.data.token); this.user.set(r.data.user); })
    );
  }

  logout() {
    const token = this.token();
    if (!token) { this.user.set(null); return; }
    this.http.post(`${environment.apiUrl}/auth/logout`, {}).subscribe({
      next: () => { this.token.set(null); this.user.set(null); },
      error: () => { this.token.set(null); this.user.set(null); }
    });
  }

  isSecretary(): boolean { return this.user()?.rol === 'secretaria'; }
}
