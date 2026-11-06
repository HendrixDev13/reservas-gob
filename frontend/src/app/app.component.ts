import { Component, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { AuthService } from './core/auth.service';
import { ApiService } from './core/api.service';

@Component({
  selector: 'app-root',
  standalone: true,
  imports: [CommonModule, FormsModule],
  template: `
  <ng-container *ngIf="!auth.user(); else appView">
    <section class="public-home">
      <header class="public-header">
        <div class="public-brand">
          <img src="assets/branding/MINGOB.png" alt="Logotipo institucional" class="public-logo">
          <div><strong>SALONES INSTITUCIONALES</strong><span>Calendario de disponibilidad</span></div>
        </div>
        <button class="public-login-button" type="button" (click)="showLogin.set(!showLogin())">
          <span class="login-button-icon">↗</span>{{showLogin() ? 'Volver al calendario' : 'Iniciar sesión'}}
        </button>
      </header>

      <main class="public-main" *ngIf="!showLogin(); else loginView">
        <div class="public-heading">
          <div><p class="public-eyebrow">DISPONIBILIDAD INSTITUCIONAL</p><h1>Calendario de reservaciones</h1><p>Selecciona una fecha para consultar las reuniones programadas y los horarios de cada salón.</p></div>
          <div class="public-today"><span class="public-live-dot"></span><span>Consulta pública</span></div>
        </div>
        <p class="public-error" *ngIf="error()">{{error()}} <button type="button" (click)="error.set(''); loadCalendar()">Reintentar</button></p>
        <div class="public-calendar-layout">
          <section class="public-card public-calendar-card">
            <div class="public-calendar-heading">
              <div><span class="public-section-label">AGENDA MENSUAL</span><h2>{{calendarMonthTitle()}}</h2></div>
              <div class="public-calendar-controls"><button type="button" (click)="moveCalendar(-1)" aria-label="Mes anterior">‹</button><button type="button" class="today-control" (click)="goToday()">Hoy</button><button type="button" (click)="moveCalendar(1)" aria-label="Mes siguiente">›</button></div>
            </div>
            <div class="public-calendar-grid" role="grid" aria-label="Calendario mensual">
              <div class="public-weekday" *ngFor="let day of weekdays">{{day}}</div>
              <button type="button" class="public-day-cell" *ngFor="let day of calendarDays()" [class.outside]="!day.inMonth" [class.selected]="day.date===fechaCalendario" [class.today-cell]="day.isToday" [class.has-events]="countForDay(day.date)>0" (click)="selectCalendarDay(day.date)" [attr.aria-label]="day.date + ', ' + countForDay(day.date) + ' reuniones'">
                <span class="public-day-number">{{day.day}}</span>
                <span class="public-day-count" *ngIf="countForDay(day.date)>0">{{countForDay(day.date)}} {{countForDay(day.date)===1 ? 'reunión':'reuniones'}}</span>
                <span class="public-day-dot" *ngIf="countForDay(day.date)>0"></span>
              </button>
            </div>
            <div class="public-calendar-legend"><span><i class="legend-blue"></i> Día seleccionado</span><span><i class="legend-event"></i> Tiene reuniones</span></div>
          </section>

          <section class="public-card public-agenda-card">
            <div class="public-agenda-heading"><div><span class="public-section-label">DETALLE DE LA FECHA</span><h2>{{selectedDayTitle()}}</h2><p>{{filteredDayReservations().length}} reuniones programadas</p></div><span class="agenda-calendar-icon">▦</span></div>
            <div class="public-agenda-list" *ngIf="filteredDayReservations().length; else publicNoMeetings">
              <article class="public-meeting" *ngFor="let r of filteredDayReservations()">
                <div class="public-meeting-time"><strong>{{r.fecha_inicio | date:'HH:mm'}}</strong><span>{{r.fecha_fin | date:'HH:mm'}}</span></div>
                <div class="public-meeting-content"><h3>{{r.titulo}}</h3><p><span class="room-pin">▣</span>{{r.salon_nombre}}</p><span class="public-meeting-status">HORARIO OCUPADO</span></div>
              </article>
            </div>
            <ng-template #publicNoMeetings><div class="public-empty"><span class="empty-calendar-icon">✓</span><h3>Sin reuniones programadas</h3><p>No hay reservaciones confirmadas para esta fecha.</p></div></ng-template>
            <p class="public-note">Para solicitar información o reservar un salón, comunícate directamente con la institución.</p>
          </section>
        </div>
        <footer class="public-footer">Calendario institucional <span>•</span> Información de consulta</footer>
      </main>

      <ng-template #loginView>
        <section class="public-login-area">
          <div class="login-wrap">
            <div class="login-brand"><img src="assets/branding/MINGOB.png" alt="Logotipo institucional" class="login-logo"><p class="eyebrow">GESTIÓN INSTITUCIONAL</p><h1>Acceso al<br><em>sistema.</em></h1><p class="muted">Ingresa con tu cuenta autorizada.</p></div>
            <form class="login-card" (ngSubmit)="login()">
              <div class="login-card-heading"><span class="login-shield">✦</span><div><p class="eyebrow">ACCESO SEGURO</p><h2>Bienvenido</h2><p class="muted">Ingresa tus credenciales.</p></div></div>
              <label>Correo institucional<input type="email" name="correo" [(ngModel)]="correo" required autocomplete="username" placeholder="nombre@institucion.gob.gt"></label>
              <label>Contraseña<input [type]="showPassword() ? 'text' : 'password'" name="password" [(ngModel)]="password" required autocomplete="current-password" placeholder="Tu contraseña"><button type="button" class="password-toggle" (click)="togglePassword()">{{showPassword() ? 'Ocultar contraseña' : 'Mostrar contraseña'}}</button></label>
              <p class="error" *ngIf="error()">{{error()}}</p>
              <button class="primary full" [disabled]="loading()">{{loading()?'Validando…':'Iniciar sesión'}}</button>
              <small class="muted center">Acceso sujeto a los permisos asignados.</small>
              <button class="back-calendar" type="button" (click)="showLogin.set(false)">← Volver al calendario público</button>
            </form>
          </div>
        </section>
      </ng-template>
    </section>

  </ng-container>

  <ng-template #appView>
    <main class="shell" [class.dark-theme]="darkMode()" [class.sidebar-collapsed]="sidebarCollapsed()">
      <aside class="sidebar" *ngIf="auth.user()">
        <div class="brand"><span class="brand-mark">RI</span><div class="brand-copy"><strong>Reservas</strong><small>Institucional</small></div><button class="sidebar-toggle" type="button" (click)="toggleSidebar()" [attr.aria-label]="sidebarCollapsed() ? 'Mostrar menú' : 'Ocultar menú'">{{sidebarCollapsed() ? '☰' : '‹'}}</button></div>
        <nav>
          <button *ngIf="auth.isSecretary()" [class.active]="section()==='dashboard'" (click)="section.set('dashboard')">▦ <span>Resumen</span></button>
          <button *ngIf="auth.isSecretary()" [class.active]="section()==='reservas'" (click)="section.set('reservas'); loadReservations()">▤ <span>Reservas</span></button>
          <button *ngIf="auth.isSecretary()" [class.active]="section()==='salones'" (click)="section.set('salones'); loadCatalogs()">▣ <span>Salones</span></button>
          <button *ngIf="auth.isSecretary()" [class.active]="section()==='equipos'" (click)="section.set('equipos'); loadCatalogs()">◇ <span>Equipamiento</span></button>
          <button [class.active]="section()==='calendario'" (click)="section.set('calendario'); loadCalendar(); loadCatalogs()">▦ <span>Calendario</span></button>
          <button *ngIf="auth.isSecretary()" [class.active]="section()==='auditoria'" (click)="section.set('auditoria'); loadAudit()">◷ <span>Auditoría</span></button>
        </nav>
        <div class="side-bottom"><span class="avatar">{{auth.user()?.nombre?.slice(0,1)}}</span><div class="profile"><b>{{auth.user()?.nombre}}</b><small>{{auth.user()?.rol}}</small></div><button class="icon-btn" (click)="auth.logout()" title="Cerrar sesión">↪</button></div>
      </aside>
      <section class="content">
        <header class="topbar"><div><p class="eyebrow">PANEL DE CONTROL</p><h1>{{sectionTitle()}}</h1></div><div class="top-actions"><span class="today">{{today}}</span><button class="theme-toggle" type="button" (click)="toggleTheme()" [attr.aria-label]="darkMode() ? 'Activar modo claro' : 'Activar modo oscuro'">{{darkMode() ? '☀' : '☾'}} <span>{{darkMode() ? 'Modo claro' : 'Modo oscuro'}}</span></button><span class="live-dot"></span></div></header>
        <p class="error banner" *ngIf="error()">{{error()}}</p>
        <ng-container [ngSwitch]="section()">
          <div *ngSwitchCase="'dashboard'">
            <div class="welcome"><div><span class="eyebrow">SISTEMA DE ESPACIOS</span><h2>Todo bajo control.</h2><p>Estado general de las reservas institucionales.</p></div><div class="welcome-symbol">▦</div></div>
            <div class="stats">
              <article class="stat stat-warning"><span>Reservas pendientes</span><strong>{{stats()?.reservas_pendientes ?? '—'}}</strong><small>Requieren revisión</small></article>
              <article class="stat stat-success"><span>Próximas confirmadas</span><strong>{{stats()?.reservas_confirmadas ?? '—'}}</strong><small>Desde hoy en adelante</small></article>
              <article class="stat stat-success"><span>Salones activos</span><strong>{{stats()?.salones_activos ?? '—'}}</strong><small>Espacios disponibles</small></article>
              <article class="stat stat-success"><span>Reservas del mes</span><strong>{{stats()?.reservas_mes ?? '—'}}</strong><small>Actividad mensual</small></article>
            </div>
            <div class="dashboard-reminder" *ngIf="upcomingReminders().length > 0"><span class="reminder-symbol">◷</span><div><strong>Recordatorio de horarios próximos</strong><small>{{upcomingReminders().length}} reserva(s) en las próximas 24 horas. Revisa la agenda y prepara el salón.</small></div><span class="reminder-count">{{upcomingReminders().length}}</span></div>
            <div class="panel"><div class="panel-heading"><div><h3>Actividad reciente</h3><p>Reservas registradas más recientemente</p></div><button class="secondary" (click)="section.set('reservas');loadReservations()">Ver todas ↗</button></div>
              <div class="table-wrap"><table><thead><tr><th>Actividad</th><th>Salón</th><th>Fecha</th><th>Horario</th><th>Estado</th></tr></thead><tbody>
              <tr *ngFor="let r of reservas().slice(0,6)"><td><b>{{r.titulo}}</b><small>{{r.solicitante}}</small></td><td>{{r.salon_nombre}}</td><td>{{r.fecha_inicio | date:'dd/MM/yyyy'}}</td><td>{{r.fecha_inicio | date:'HH:mm'}} – {{r.fecha_fin | date:'HH:mm'}}</td><td><span class="status" [class]="statusClass(r.estado)">{{r.estado}}</span></td></tr>
              <tr *ngIf="reservas().length===0"><td colspan="5" class="empty">No hay reservas para mostrar. Abre la sección Reservas para consultar.</td></tr>
              </tbody></table></div>
            </div>
          </div>
          <div *ngSwitchCase="'reservas'">
            <div class="panel"><div class="panel-heading"><div><h3>Gestión de reservas</h3><p>Consulta la agenda y administra solicitudes.</p></div><button *ngIf="auth.isSecretary()" class="primary" (click)="showForm.set(!showForm())">＋ Nueva reserva</button></div>
              <form class="create-form" *ngIf="showForm()" (ngSubmit)="createReservation()">
                <label>Título<input name="titulo" [(ngModel)]="form.titulo" required></label><label>Solicitante<input name="solicitante" [(ngModel)]="form.solicitante" required></label>
                <label>Salón<select name="salon_id" [(ngModel)]="form.salon_id" required><option [ngValue]="0">Selecciona…</option><option *ngFor="let s of salones()" [ngValue]="s.id">{{s.nombre}}</option></select></label>
                <label>Dependencia<input name="dependencia" [(ngModel)]="form.dependencia"></label>
                <label>Inicio<input type="datetime-local" name="inicio" [(ngModel)]="form.inicio" required></label><label>Fin<input type="datetime-local" name="fin" [(ngModel)]="form.fin" required></label>
                <label class="wide">Descripción<input name="descripcion" [(ngModel)]="form.descripcion"></label>
                <div class="equipment-picker wide"><strong>Equipamiento requerido</strong><p>Selecciona los recursos que deben prepararse para la reunión.</p><div class="equipment-options"><label *ngFor="let e of equipos()" class="equipment-option"><input type="checkbox" [checked]="selectedEquipmentIds.includes(e.id)" (change)="toggleEquipment(e.id, $any($event.target).checked)"><span>{{equipmentIcon(e.nombre)}}</span><b>{{e.nombre}}</b></label><label class="equipment-option" *ngFor="let item of defaultEquipment" [hidden]="hasEquipmentName(item.name)"><input type="checkbox" [checked]="selectedEquipmentNames.includes(item.name)" (change)="toggleEquipmentName(item.name, $any($event.target).checked)"><span>{{item.icon}}</span><b>{{item.name}}</b></label></div></div>
                <div class="form-actions"><button type="button" class="secondary" (click)="showForm.set(false)">Cancelar</button><button class="primary">Guardar solicitud</button></div>
              </form>
              <div class="table-wrap"><table><thead><tr><th>Actividad</th><th>Salón</th><th>Fecha</th><th>Horario</th><th>Estado</th><th *ngIf="auth.isSecretary()">Acciones</th></tr></thead><tbody>
              <tr *ngFor="let r of reservas()"><td><b>{{r.titulo}}</b><small>{{r.solicitante}}</small></td><td>{{r.salon_nombre}}</td><td>{{r.fecha_inicio | date:'dd/MM/yyyy'}}</td><td>{{r.fecha_inicio | date:'HH:mm'}} – {{r.fecha_fin | date:'HH:mm'}}</td><td><span class="status" [class]="statusClass(r.estado)">{{r.estado}}</span></td>
              <td *ngIf="auth.isSecretary()" class="actions"><button *ngIf="r.estado==='pendiente'" (click)="changeStatus(r.id,'confirmar')">Confirmar</button><button *ngIf="r.estado==='pendiente'||r.estado==='confirmada'" (click)="changeStatus(r.id,'cancelar')">Cancelar</button><button *ngIf="r.estado==='confirmada'" (click)="changeStatus(r.id,'finalizar')">Finalizar</button></td></tr>
              <tr *ngIf="reservas().length===0"><td colspan="6" class="empty">No hay reservas registradas.</td></tr></tbody></table></div>
            </div>
          </div>
          <div *ngSwitchCase="'calendario'">
            <div class="calendar-layout">
              <section class="panel calendar-panel">
                <div class="panel-heading calendar-heading">
                  <div><p class="eyebrow">DISPONIBILIDAD</p><h3>{{calendarMonthTitle()}}</h3><p>Selecciona un día para consultar las actividades.</p></div>
                  <div class="calendar-controls">
                    <button class="secondary" type="button" (click)="moveCalendar(-1)" aria-label="Mes anterior">‹</button>
                    <button class="secondary" type="button" (click)="goToday()">Hoy</button>
                    <button class="secondary" type="button" (click)="moveCalendar(1)" aria-label="Mes siguiente">›</button>
                  </div>
                </div>
                <div class="calendar-toolbar">
                  <label class="filter-label">Salón
                    <select [(ngModel)]="calendarSalonFilter" (ngModelChange)="applyCalendarFilter()">
                      <option value="todos">Todos los salones</option>
                      <option *ngFor="let s of salones()" [value]="s.id">{{s.nombre}}</option>
                    </select>
                  </label>
                  <label class="filter-label">Ir a fecha <input type="date" [(ngModel)]="fechaCalendario" (change)="goToCalendarDate()"></label>
                  <div class="calendar-legend"><span><i class="legend-dot reserved"></i> Con reservas</span><span><i class="legend-dot free"></i> Sin reservas</span></div>
                </div>
                <div class="calendar-grid" role="grid" aria-label="Calendario mensual">
                  <div class="weekday" *ngFor="let day of weekdays">{{day}}</div>
                  <button type="button" class="day-cell" *ngFor="let day of calendarDays()" [class.outside]="!day.inMonth" [class.selected]="day.date===fechaCalendario" [class.today-cell]="day.isToday" (click)="selectCalendarDay(day.date)" [attr.aria-label]="day.date">
                    <span class="day-number">{{day.day}}</span>
                    <span class="day-count" *ngIf="countForDay(day.date)>0">{{countForDay(day.date)}} {{countForDay(day.date)===1 ? 'reserva':'reservas'}}</span>
                    <span class="day-dots" *ngIf="countForDay(day.date)>0"><i *ngFor="let dot of dotsForDay(day.date).slice(0,3)" [style.background]="dot"></i></span>
                  </button>
                </div>
                <div class="calendar-footnote">La disponibilidad refleja las reservas devueltas por el servidor. Confirma el horario antes de registrar una nueva reunión.</div>
              </section>
              <section class="panel agenda-panel">
                <div class="panel-heading"><div><p class="eyebrow">AGENDA DEL DÍA</p><h3>{{selectedDayTitle()}}</h3><p>{{filteredDayReservations().length}} actividades programadas</p></div><button *ngIf="auth.isSecretary()" class="primary" type="button" (click)="startReservationForDay()">＋ Nueva reserva</button></div>
                <div class="agenda-list" *ngIf="filteredDayReservations().length; else noDayReservations">
                  <article class="agenda-item" *ngFor="let r of filteredDayReservations()">
                    <div class="agenda-time"><strong>{{r.fecha_inicio | date:'HH:mm'}}</strong><span>{{r.fecha_fin | date:'HH:mm'}}</span></div>
                    <div class="agenda-line" [class]="statusClass(r.estado)"></div>
                    <div class="agenda-details"><div class="agenda-title-row"><h4>{{r.titulo}}</h4><span class="status" [class]="statusClass(r.estado)">{{r.estado}}</span></div><p>{{r.salon_nombre}} <span>·</span> {{r.solicitante}}</p><small>{{r.dependencia || 'Sin dependencia'}}</small><div class="reservation-equipment" *ngIf="reservationEquipment(r).length"><span *ngFor="let item of reservationEquipment(r)">{{equipmentIcon(item)}} {{item}}</span></div></div>
                  </article>
                </div>
                <ng-template #noDayReservations><div class="empty-agenda"><span>✓</span><strong>No hay reservas este día</strong><p>El calendario no muestra actividades para la fecha seleccionada.</p><button *ngIf="auth.isSecretary()" class="secondary" (click)="startReservationForDay()">Crear una reserva</button></div></ng-template>
              </section>
            </div>
          </div>
          <div *ngSwitchCase="'auditoria'">
            <div class="panel"><div class="panel-heading"><div><h3>Historial de auditoría</h3><p>Últimas acciones registradas por el sistema.</p></div><button class="secondary" (click)="loadAudit()">Actualizar</button></div>
              <div class="table-wrap"><table><thead><tr><th>Fecha</th><th>Usuario</th><th>Acción</th><th>Entidad</th><th>ID</th></tr></thead><tbody>
                <tr *ngFor="let a of auditoria()"><td>{{a.creado_en | date:'dd/MM/yyyy HH:mm'}}</td><td>{{a.usuario || 'Sistema'}}</td><td>{{a.accion}}</td><td>{{a.entidad}}</td><td>{{a.entidad_id || '—'}}</td></tr>
                <tr *ngIf="auditoria().length===0"><td colspan="5" class="empty">No hay eventos de auditoría registrados.</td></tr>
              </tbody></table></div>
            </div>
          </div>
          <div *ngSwitchCase="'salones'">
            <div class="panel"><div class="panel-heading"><div><h3>Salones institucionales</h3><p>Espacios registrados en el sistema.</p></div></div>
              <form class="inline-form" *ngIf="auth.isSecretary()" (ngSubmit)="createSalon()"><input name="salonNombre" [(ngModel)]="salonNombre" placeholder="Nombre del salón" required><input type="number" name="capacidad" [(ngModel)]="capacidad" min="1" placeholder="Capacidad" required><input name="ubicacion" [(ngModel)]="ubicacion" placeholder="Ubicación"><button class="primary">Agregar</button></form>
              <div class="catalog-grid"><article class="catalog-card room-card" *ngFor="let s of salones()"><div class="catalog-visual room-visual" [ngClass]="roomVisualClass(s.nombre)"><span>{{roomIcon(s.nombre)}}</span><small>REFERENCIA DEL ESPACIO</small></div><h3>{{s.nombre}}</h3><p>{{s.ubicacion || 'Ubicación no definida'}}</p><div class="catalog-meta"><span>Capacidad</span><b>{{s.capacidad}} personas</b></div><span class="status good">{{s.activo ? 'Activo':'Inactivo'}}</span></article></div>
            </div>
          </div>
          <div *ngSwitchCase="'equipos'">
            <div class="panel"><div class="panel-heading"><div><h3>Equipamiento</h3><p>Recursos institucionales registrados.</p></div></div>
              <form class="inline-form" *ngIf="auth.isSecretary()" (ngSubmit)="createEquipment()"><input name="equipoNombre" [(ngModel)]="equipoNombre" placeholder="Nombre del equipo" required><input type="number" name="cantidad" [(ngModel)]="cantidad" min="1" placeholder="Cantidad" required><button class="primary">Agregar</button></form>
              <div class="catalog-grid"><article class="catalog-card equipment-card" *ngFor="let e of equipos()"><div class="catalog-visual equipment-visual"><span>{{equipmentIcon(e.nombre)}}</span><small>RECURSO INSTITUCIONAL</small></div><h3>{{e.nombre}}</h3><p>{{e.descripcion || 'Recurso institucional'}}</p><div class="catalog-meta"><span>Cantidad total</span><b>{{e.cantidad_total}} unidades</b></div><span class="status good">{{e.activo ? 'Activo':'Inactivo'}}</span></article></div>
            </div>
          </div>
        </ng-container>
        <footer>Reservas Institucionales <span>•</span> Sistema de gestión y control</footer>
      </section>
    </main>
  </ng-template>
  `,
})
export class AppComponent {
  section = signal('dashboard');
  stats = signal<any>(null);
  reservas = signal<any[]>([]);
  salones = signal<any[]>([]);
  equipos = signal<any[]>([]);
  calendarReservas = signal<any[]>([]);
  calendarMonth = new Date(new Date().getFullYear(), new Date().getMonth(), 1);
  calendarSalonFilter = 'todos';
  darkMode = signal(false);
  sidebarCollapsed = signal(false);
  showPassword = signal(false);
  showLogin = signal(false);
  selectedEquipmentIds: number[] = [];
  selectedEquipmentNames: string[] = [];
  defaultEquipment = [{name:'Cafetera',icon:'☕'},{name:'Cañonera',icon:'📽️'},{name:'Sonido',icon:'🔊'},{name:'Manteles',icon:'▤'}];
  weekdays = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
  auditoria = signal<any[]>([]);
  fechaCalendario = new Date(Date.now() - new Date().getTimezoneOffset() * 60000).toISOString().slice(0,10);
  error = signal('');
  loading = signal(false);
  showForm = signal(false);
  correo = ''; password = '';
  salonNombre = ''; capacidad = 1; ubicacion = '';
  equipoNombre = ''; cantidad = 1;
  form: any = { salon_id: 0, titulo: '', solicitante: '', dependencia: '', inicio: '', fin: '', descripcion: '' };
  today = new Date().toLocaleDateString('es-GT', { weekday:'long', day:'numeric', month:'long', year:'numeric' });

  constructor(public auth: AuthService, private api: ApiService) {}
  togglePassword(): void {
  this.showPassword.update(value => !value);
}
  sectionTitle(): string {
    if (!this.auth.isSecretary()) return 'Calendario institucional';
    return ({dashboard:'Resumen general',reservas:'Reservas',salones:'Salones',equipos:'Equipamiento',calendario:'Calendario',auditoria:'Auditoría'} as Record<string,string>)[this.section()] || 'Panel';
  }

  ngOnInit() {
    this.auth.user();
    try { this.darkMode.set(localStorage.getItem('reservas-theme') === 'dark'); } catch {}
    if (!this.auth.user()) this.loadCalendar();
  }
  toggleSidebar() { this.sidebarCollapsed.update(value => !value); }
  toggleEquipment(id:number, checked:boolean) { this.selectedEquipmentIds = checked ? [...new Set([...this.selectedEquipmentIds,id])] : this.selectedEquipmentIds.filter(value=>value!==id); }
  toggleEquipmentName(name:string, checked:boolean) { this.selectedEquipmentNames = checked ? [...new Set([...this.selectedEquipmentNames,name])] : this.selectedEquipmentNames.filter(value=>value!==name); }
  hasEquipmentName(name:string): boolean { return this.equipos().some(e => String(e.nombre).trim().toLocaleLowerCase() === name.toLocaleLowerCase()); }
  equipmentIcon(name:string): string { const n=String(name||'').toLocaleLowerCase(); if(n.includes('cafe')) return '☕'; if(n.includes('cañon')||n.includes('proyector')) return '📽️'; if(n.includes('sonido')||n.includes('audio')) return '🔊'; if(n.includes('mantel')) return '▤'; if(n.includes('mesa')) return '▱'; if(n.includes('silla')) return '🪑'; if(n.includes('comput')) return '💻'; return '◇'; }
  roomIcon(name:string): string { const n=String(name||'').toLocaleLowerCase(); return n.includes('auditorio') ? '▤' : n.includes('conferencia') ? '▦' : '▣'; }
  roomVisualClass(name:string): string { const n=String(name||'').toLocaleLowerCase(); return n.includes('auditorio') ? 'room-auditorium' : n.includes('conferencia') ? 'room-conference' : 'room-meeting'; }
  reservationEquipment(r:any): string[] { const value=r.equipamiento ?? r.equipos ?? r.equipamiento_nombres ?? r.recursos; if(Array.isArray(value)) return value.map((v:any)=>typeof v==='string'?v:(v.nombre||v.name||'')).filter(Boolean); if(typeof value==='string' && value.trim()) return value.split(',').map(v=>v.trim()).filter(Boolean); return []; }
  toggleTheme() {
    this.darkMode.update(value => !value);
    try { localStorage.setItem('reservas-theme', this.darkMode() ? 'dark' : 'light'); } catch {}
  }
  login() {
    this.error.set(''); this.loading.set(true);
    this.auth.login(this.correo,this.password).subscribe({
      next: () => { this.loading.set(false); this.showLogin.set(false); this.password=''; if (this.auth.isSecretary()) { this.section.set('dashboard'); this.loadAll(); } else { this.section.set('calendario'); this.loadCalendar(); this.loadCatalogs(); } },
      error: e => { this.loading.set(false); this.error.set(e?.error?.error?.message || 'No fue posible iniciar sesión. Verifica tus datos y la conexión con la API.'); }
    });
  }
  loadAll() {
    if (!this.auth.isSecretary()) { this.section.set('calendario'); this.loadCalendar(); this.loadCatalogs(); return; }
    this.api.dashboard().subscribe({next:r=>this.stats.set(r.data),error:e=>this.handle(e)});
    this.loadCatalogs(); this.loadReservations();
  }
  loadReservations() { if (!this.auth.isSecretary()) { this.section.set('calendario'); this.loadCalendar(); return; } this.api.reservas().subscribe({next:r=>this.reservas.set(r.data),error:e=>this.handle(e)}); }
  loadCalendar() {
    const from = this.dateKey(new Date(this.calendarMonth.getFullYear(), this.calendarMonth.getMonth(), 1));
    const to = this.dateKey(new Date(this.calendarMonth.getFullYear(), this.calendarMonth.getMonth() + 1, 1));
    this.api.reservas(from, to).subscribe({
      next: r => this.calendarReservas.set((r.data || []).filter((item: any) => this.auth.user() ? item.estado !== 'cancelada' : item.estado === 'confirmada')),
      error: e => this.handle(e)
    });
  }
  dateKey(date: Date): string {
    return `${date.getFullYear()}-${String(date.getMonth()+1).padStart(2,'0')}-${String(date.getDate()).padStart(2,'0')}`;
  }
  calendarMonthTitle(): string {
    return this.calendarMonth.toLocaleDateString('es-GT', { month: 'long', year: 'numeric' });
  }
  calendarDays(): Array<{date:string,day:number,inMonth:boolean,isToday:boolean}> {
    const first = new Date(this.calendarMonth.getFullYear(), this.calendarMonth.getMonth(), 1);
    const offset = (first.getDay() + 6) % 7;
    const start = new Date(first.getFullYear(), first.getMonth(), 1 - offset);
    const today = this.dateKey(new Date());
    return Array.from({length:42}, (_,i) => {
      const d = new Date(start.getFullYear(), start.getMonth(), start.getDate()+i);
      return {date:this.dateKey(d),day:d.getDate(),inMonth:d.getMonth()===this.calendarMonth.getMonth(),isToday:this.dateKey(d)===today};
    });
  }
  countForDay(date:string): number {
    return this.filteredCalendarReservations().filter(r => String(r.fecha_inicio).slice(0,10) === date).length;
  }
  dotsForDay(date:string): string[] {
    const palette = ['#4776e6','#0f9f76','#d58b22'];
    const rooms = new Set(this.filteredCalendarReservations().filter(r => String(r.fecha_inicio).slice(0,10)===date).map(r => String(r.salon_id ?? r.salon_nombre)));
    return Array.from(rooms).map((_,i)=>palette[i%palette.length]);
  }
  filteredCalendarReservations(): any[] {
    return this.calendarReservas().filter(r => this.calendarSalonFilter === 'todos' || String(r.salon_id) === String(this.calendarSalonFilter));
  }
  filteredDayReservations(): any[] {
    return this.filteredCalendarReservations()
      .filter(r => String(r.fecha_inicio).slice(0,10) === this.fechaCalendario)
      .sort((a,b)=>String(a.fecha_inicio).localeCompare(String(b.fecha_inicio)));
  }
  selectedDayTitle(): string {
    const d = new Date(`${this.fechaCalendario}T12:00:00`);
    return d.toLocaleDateString('es-GT',{weekday:'long',day:'numeric',month:'long'});
  }
  selectCalendarDay(date:string) { this.fechaCalendario=date; }
  applyCalendarFilter() {}
  moveCalendar(delta:number) {
    this.calendarMonth = new Date(this.calendarMonth.getFullYear(),this.calendarMonth.getMonth()+delta,1);
    this.fechaCalendario = this.dateKey(this.calendarMonth);
    this.loadCalendar();
  }
  goToday() {
    const now = new Date();
    this.calendarMonth = new Date(now.getFullYear(),now.getMonth(),1);
    this.fechaCalendario = this.dateKey(now);
    this.loadCalendar();
  }
  goToCalendarDate() {
    const d = new Date(`${this.fechaCalendario}T12:00:00`);
    if (!Number.isNaN(d.getTime())) {
      this.calendarMonth = new Date(d.getFullYear(),d.getMonth(),1);
      this.loadCalendar();
    }
  }
  startReservationForDay() {
    if (!this.auth.isSecretary()) return;
    this.form = {...this.form, inicio:`${this.fechaCalendario}T09:00`, fin:`${this.fechaCalendario}T10:00`};
    this.showForm.set(true);
    this.section.set('reservas');
    this.loadCatalogs();
  }
  loadAudit() { if (!this.auth.isSecretary()) { this.section.set('calendario'); return; } this.api.auditoria().subscribe({next:r=>this.auditoria.set(r.data),error:e=>this.handle(e)}); }
  loadCatalogs() {
    this.api.salones().subscribe({next:r=>this.salones.set(r.data),error:e=>this.handle(e)});
    this.api.equipos().subscribe({next:r=>this.equipos.set(r.data),error:e=>this.handle(e)});
  }
  createReservation() {
    if (!this.auth.isSecretary()) { this.section.set('calendario'); return; }
    const payload = {...this.form, equipamiento_ids:this.selectedEquipmentIds, equipamiento_solicitado:this.selectedEquipmentNames, fecha_inicio:this.form.inicio.replace('T',' ')+':00', fecha_fin:this.form.fin.replace('T',' ')+':00'};
    delete payload.inicio; delete payload.fin;
    this.api.crearReserva(payload).subscribe({
      next:()=>{this.showForm.set(false);this.form={salon_id:0,titulo:'',solicitante:'',dependencia:'',inicio:'',fin:'',descripcion:''};this.selectedEquipmentIds=[];this.selectedEquipmentNames=[];this.loadAll();this.loadCalendar();this.error.set('');},
      error:e=>this.handle(e)
    });
  }
  changeStatus(id:number, action:string) {
    const motivo=action==='cancelar' ? (prompt('Motivo de cancelación (opcional):') || '') : '';
    this.api.cambiarEstado(id,action,motivo).subscribe({next:()=>this.loadAll(),error:e=>this.handle(e)});
  }
  createSalon() {
    if (!this.auth.isSecretary()) return;
    this.api.crearSalon({nombre:this.salonNombre,capacidad:this.capacidad,ubicacion:this.ubicacion}).subscribe({
      next:()=>{this.salonNombre='';this.capacidad=1;this.ubicacion='';this.loadCatalogs();},
      error:e=>this.handle(e)
    });
  }
  createEquipment() {
    if (!this.auth.isSecretary()) return;
    this.api.crearEquipo({nombre:this.equipoNombre,cantidad_total:this.cantidad}).subscribe({
      next:()=>{this.equipoNombre='';this.cantidad=1;this.loadCatalogs();},
      error:e=>this.handle(e)
    });
  }
  upcomingReminders(): any[] { const now=Date.now(); const limit=now+24*60*60*1000; return this.reservas().filter(r=>r.estado==='confirmada' && new Date(r.fecha_inicio).getTime()>=now && new Date(r.fecha_inicio).getTime()<=limit); }
  statusClass(status:string) { return ({pendiente:'pending',confirmada:'good',cancelada:'bad',finalizada:'neutral'} as Record<string,string>)[status] || 'neutral'; }
  handle(e:any) { this.error.set(e?.error?.error?.message || 'Error de comunicación con el servidor. Consulta los logs con el identificador de solicitud.'); }
}
