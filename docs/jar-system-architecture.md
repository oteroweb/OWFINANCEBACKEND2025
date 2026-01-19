# 🏗️ Arquitectura del Sistema de Cántaros

## 📊 Diagrama de Entidad-Relación (Cántaros y Ajustes)

```mermaid
erDiagram
    USERS ||--o{ JARS : "owns"
    USERS ||--o{ JAR_ADJUSTMENTS : "makes"
    JARS ||--o{ JAR_ADJUSTMENTS : "has history"
    JARS ||--o{ JAR_CATEGORY : "links to"
    JARS ||--o{ JAR_BASE_CATEGORY : "income from"
    CATEGORIES ||--o{ JAR_CATEGORY : "expenses in"
    CATEGORIES ||--o{ JAR_BASE_CATEGORY : "income in"
    JARS ||--o{ ITEM_TRANSACTIONS : "tracks spending"
    ITEM_TRANSACTIONS }o--|| TRANSACTIONS : "part of"
    TRANSACTIONS }o--|| TRANSACTION_TYPES : "typed as"

    USERS {
        bigint id PK
        varchar name
        decimal balance
        decimal monthly_income "Ingreso esperado mensual"
        bigint currency_id FK
        timestamp created_at
    }

    JARS {
        bigint id PK
        bigint user_id FK
        varchar name "Ej: Necesidades, Ahorro"
        enum type "percent o fixed"
        decimal percent "% del ingreso (si type=percent)"
        decimal fixed_amount "Monto fijo (si type=fixed)"
        enum base_scope "all_income o categories"
        enum refresh_mode "reset o accumulative"
        decimal adjustment "Ajuste manual acumulado"
        varchar color
        int sort_order
        boolean active
        timestamp created_at
    }

    JAR_ADJUSTMENTS {
        bigint id PK
        bigint jar_id FK
        bigint user_id FK
        decimal amount "Monto del ajuste"
        enum type "increment o decrement"
        text reason "Motivo del ajuste"
        decimal previous_available "Balance antes"
        decimal new_available "Balance después"
        date adjustment_date "Fecha del ajuste"
        timestamp created_at
    }

    JAR_CATEGORY {
        bigint id PK
        bigint jar_id FK
        bigint category_id FK "Categorías de GASTO"
        timestamp created_at
    }

    JAR_BASE_CATEGORY {
        bigint id PK
        bigint jar_id FK
        bigint category_id FK "Categorías de INGRESO"
        timestamp created_at
    }

    CATEGORIES {
        bigint id PK
        varchar name
        bigint transaction_type_id FK
        bigint user_id FK
        boolean active
    }

    ITEM_TRANSACTIONS {
        bigint id PK
        bigint transaction_id FK
        bigint jar_id FK
        bigint category_id FK
        bigint user_id FK
        decimal amount
        date date
        timestamp created_at
    }

    TRANSACTIONS {
        bigint id PK
        bigint transaction_type_id FK
        decimal amount
        datetime date
    }

    TRANSACTION_TYPES {
        bigint id PK
        varchar slug "income o expense"
    }
```

---

## 🔄 Flujo de Cálculo de Balance

```mermaid
graph TB
    Start([Usuario consulta balance de un Cántaro]) --> CheckDate{¿Tiene parámetro de fecha?}

    CheckDate -->|Sí| SetDate[Fecha = parámetro recibido]
    CheckDate -->|No| SetToday[Fecha = HOY]

    SetDate --> CheckType{Tipo de Cántaro}
    SetToday --> CheckType

    CheckType -->|Fixed| CalcFixed[Monto Asignado = fixed_amount]
    CheckType -->|Percent| CheckScope{base_scope?}

    CheckScope -->|all_income| CalcAllIncome[Suma TODOS los ingresos del mes]
    CheckScope -->|categories| CalcCatIncome[Suma ingresos de base_categories del mes]

    CalcAllIncome --> ApplyPercent[Monto Asignado = Ingreso × percent / 100]
    CalcCatIncome --> ApplyPercent

    CalcFixed --> GetSpent[Calcular Gastos del Mes]
    ApplyPercent --> GetSpent

    GetSpent --> QueryTransactions[Buscar ITEM_TRANSACTIONS del mes<br/>donde jar_id = X<br/>o category_id en JAR_CATEGORY]

    QueryTransactions --> SumSpent[Gastos = SUM de amounts]

    SumSpent --> GetAdjustment[Obtener jar.adjustment]

    GetAdjustment --> Formula[Balance = Monto Asignado - Gastos + Ajuste]

    Formula --> Response[Retornar Balance Detallado]

    Response --> End([Fin])

    style Start fill:#e1f5fe
    style End fill:#c8e6c9
    style Formula fill:#fff9c4
    style Response fill:#f3e5f5
```

---

## 📅 Flujo de Navegación por Fechas/Meses

```mermaid
sequenceDiagram
    participant User as Usuario
    participant UI as Widget de Fechas
    participant API as Backend API
    participant DB as Base de Datos

    User->>UI: Selecciona mes (ej: Nov 2025)
    UI->>UI: Actualiza estado local (month = 2025-11)

    UI->>API: GET /jars?month=2025-11
    Note over API: Para cada cántaro del usuario

    API->>DB: Buscar configuración del cántaro
    DB-->>API: JARS (type, percent, fixed_amount, etc.)

    API->>DB: ¿Qué ingreso tenía en Nov 2025?
    Note over DB: ⚠️ PROBLEMA ACTUAL:<br/>NO hay tabla de historial<br/>Solo existe users.monthly_income<br/>(valor único/actual)
    DB-->>API: monthly_income (valor actual, no histórico)

    API->>DB: Buscar transacciones de ingreso en Nov 2025
    DB-->>API: SUM(ITEM_TRANSACTIONS) donde type=income

    API->>DB: Buscar transacciones de gasto en Nov 2025
    DB-->>API: SUM(ITEM_TRANSACTIONS) donde category_id in JAR_CATEGORY

    API->>DB: Buscar ajustes hasta Nov 2025
    DB-->>API: SUM(JAR_ADJUSTMENTS) donde date <= 2025-11-30

    API->>API: Calcular Balance = (Asignado - Gastado) + Ajuste
    API-->>UI: Balances por cántaro

    UI->>UI: Renderizar cántaros con balance histórico
    UI-->>User: Muestra vista de Nov 2025

    Note over User,DB: ⚠️ LIMITACIÓN:<br/>No se guarda qué % o monto<br/>tenía cada cántaro en ese mes
```

---

## 🎯 Sistema de Ajustes (jar_adjustments)

```mermaid
graph LR
    A[Usuario hace ajuste manual] --> B{Tipo de ajuste}

    B -->|Positivo| C[Incremento]
    B -->|Negativo| D[Decremento]

    C --> E[Registrar en jar.adjustment<br/>jar.adjustment += amount]
    D --> E

    E --> F[Crear registro en jar_adjustments]

    F --> G[Guardar auditoría:<br/>- amount<br/>- type<br/>- reason<br/>- previous_available<br/>- new_available<br/>- adjustment_date<br/>- user_id]

    G --> H{refresh_mode?}

    H -->|reset| I[En nuevo mes:<br/>jar.adjustment = 0]
    H -->|accumulative| J[En nuevo mes:<br/>jar.adjustment mantiene valor]

    I --> K[Balance fresh cada mes]
    J --> L[Balance acumulativo]

    style A fill:#e1f5fe
    style G fill:#fff9c4
    style K fill:#ffcdd2
    style L fill:#c8e6c9
```

---

## 🔢 Fórmula de Cálculo (Detallada)

```mermaid
graph TB
    subgraph "1. MONTO ASIGNADO"
        A1{Tipo}
        A1 -->|Fixed| A2[Monto = fixed_amount]
        A1 -->|Percent| A3{base_scope}

        A3 -->|all_income| A4[Ingreso = Σ todas transacciones<br/>tipo 'income' del mes]
        A3 -->|categories| A5[Ingreso = Σ transacciones 'income'<br/>de base_categories del mes]

        A4 --> A6[Monto = Ingreso × percent / 100]
        A5 --> A6

        A2 --> A7[MONTO ASIGNADO]
        A6 --> A7
    end

    subgraph "2. GASTOS"
        B1[Buscar ITEM_TRANSACTIONS del mes]
        B1 --> B2{Cántaro tiene<br/>categorías?}

        B2 -->|Sí| B3[Filtrar por category_id<br/>en JAR_CATEGORY]
        B2 -->|No| B4[Filtrar por jar_id directo]

        B3 --> B5[Gastos = Σ amounts]
        B4 --> B5
    end

    subgraph "3. AJUSTES"
        C1[jar.adjustment]
        C1 --> C2{refresh_mode}

        C2 -->|reset| C3[Si es nuevo mes:<br/>adjustment = 0]
        C2 -->|accumulative| C4[Mantiene valor<br/>acumulado]

        C3 --> C5[AJUSTE]
        C4 --> C5
    end

    A7 --> Formula
    B5 --> Formula
    C5 --> Formula

    Formula[Balance Disponible =<br/>MONTO ASIGNADO - GASTOS + AJUSTE]

    Formula --> Result([Resultado Final])

    style A7 fill:#c8e6c9
    style B5 fill:#ffcdd2
    style C5 fill:#fff9c4
    style Formula fill:#e1f5fe,stroke:#1976d2,stroke-width:3px
    style Result fill:#f3e5f5
```

---

## ⚠️ PROBLEMA DETECTADO: Navegación Histórica

### Estado Actual

```mermaid
graph TB
    subgraph "DATOS ACTUALES"
        U[users.monthly_income<br/>Valor único/estático]
        J[jars.percent<br/>jars.fixed_amount<br/>Valores actuales]
    end

    subgraph "CONSULTA POR MES"
        Q1[Usuario pide Nov 2025]
        Q2[Usuario pide Dic 2025]
        Q3[Usuario pide Ene 2026]
    end

    Q1 --> U
    Q2 --> U
    Q3 --> U

    Q1 --> J
    Q2 --> J
    Q3 --> J

    U --> R[❌ Siempre devuelve<br/>el mismo monthly_income<br/>no el de ese mes]
    J --> R

    R --> P[❌ No se puede recuperar<br/>la configuración histórica]

    style R fill:#ffcdd2
    style P fill:#ffcdd2
```

### Solución Propuesta

```mermaid
graph TB
    subgraph "NUEVA TABLA: user_monthly_income_history"
        H1[id]
        H2[user_id]
        H3[monthly_income]
        H4[month DATE]
        H5[created_at]
    end

    subgraph "CONSULTA MEJORADA"
        Q[GET /jars?month=2025-11]
        Q --> S1[Buscar en user_monthly_income_history<br/>WHERE month = '2025-11-01']

        S1 --> S2{¿Encontrado?}
        S2 -->|Sí| S3[Usar monthly_income histórico]
        S2 -->|No| S4[Usar users.monthly_income actual]

        S3 --> S5[Calcular con ingreso correcto]
        S4 --> S5
    end

    subgraph "REGISTRO DE CAMBIOS"
        C[Usuario cambia monthly_income]
        C --> C1[Actualizar users.monthly_income]
        C --> C2[INSERT INTO user_monthly_income_history<br/>month = primer día del mes actual]
    end

    style S3 fill:#c8e6c9
    style S5 fill:#c8e6c9
    style C2 fill:#fff9c4
```

---

## 📊 Ejemplo Completo: Caso de Uso Real

### Escenario: Usuario con ingresos variables

```mermaid
gantt
    title Evolución de Ingresos y Cántaros
    dateFormat YYYY-MM

    section Ingresos
    Nov 2025: $1,200     :a1, 2025-11, 30d
    Dic 2025: $2,000     :a2, 2025-12, 31d
    Ene 2026: $1,500     :a3, 2026-01, 31d

    section Necesidades (50%)
    $600 (Nov)           :b1, 2025-11, 30d
    $1,000 (Dic)         :b2, 2025-12, 31d
    $750 (Ene)           :b3, 2026-01, 31d

    section Ahorro (30%)
    $360 (Nov)           :c1, 2025-11, 30d
    $600 (Dic)           :c2, 2025-12, 31d
    $450 (Ene)           :c3, 2026-01, 31d
```

**Con el sistema actual:**
- ❌ Si navegas a Nov 2025, usa el `monthly_income` actual (no $1,200)
- ❌ No sabe que en Dic tenías configurado $2,000

**Con la solución propuesta:**
- ✅ Navegas a Nov 2025 → recupera `monthly_income = $1,200` histórico
- ✅ Navegas a Dic 2025 → recupera `monthly_income = $2,000` histórico
- ✅ Calcula correctamente cada cántaro según su configuración de ese mes

---

## 🎯 Implementación Recomendada

### 1. Migración (Nueva Tabla)

```sql
CREATE TABLE user_monthly_income_history (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    monthly_income DECIMAL(10,2) NOT NULL,
    month DATE NOT NULL COMMENT 'Primer día del mes (2025-11-01)',
    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_month (user_id, month),
    INDEX idx_user_month (user_id, month)
);
```

### 2. Modificar Endpoint GET /jars

```php
// Aceptar parámetro ?month=2025-11
$month = $request->get('month');
$monthlyIncome = $this->getMonthlyIncomeForMonth($user->id, $month);

private function getMonthlyIncomeForMonth(int $userId, ?string $month): float
{
    if (!$month) {
        return User::find($userId)->monthly_income;
    }

    $firstDayOfMonth = Carbon::parse($month)->startOfMonth();

    $history = UserMonthlyIncomeHistory::where('user_id', $userId)
        ->where('month', $firstDayOfMonth)
        ->first();

    return $history?->monthly_income ?? User::find($userId)->monthly_income;
}
```

### 3. Widget de Navegación Frontend

```typescript
// Cuando usuario cambia de mes
async function onMonthChange(month: string) {
  const response = await api.get(`/jars/income-summary?month=${month}`);
  const jarsResponse = await api.get(`/jars?month=${month}`);

  // Actualizar UI con datos históricos
  updateIncomePanelWithHistoricData(response.data);
  updateJarsWithHistoricBalances(jarsResponse.data);
}
```

---

## 🔗 Relación con Tablas Existentes

```mermaid
graph LR
    A[USERS] --> B[user_monthly_income_history]
    A --> C[JARS]
    C --> D[JAR_ADJUSTMENTS]
    C --> E[JAR_CATEGORY]
    C --> F[JAR_BASE_CATEGORY]
    C --> G[ITEM_TRANSACTIONS]

    B -.->|Histórico de| A
    D -.->|Auditoría de| C

    style B fill:#fff9c4,stroke:#f57f17,stroke-width:3px
    style D fill:#e1f5fe
    style A fill:#c8e6c9
    style C fill:#c8e6c9
```

---

## 📝 Resumen

1. **Estructura actual**: Completa para cálculos en tiempo real
2. **Problema**: No hay historial de `monthly_income` ni configuración de cántaros
3. **Solución**: Tabla `user_monthly_income_history` para guardar cambios mes a mes
4. **Beneficio**: Navegación histórica precisa, mostrando ingresos y cántaros de cada período
5. **Compatible**: Se integra sin romper funcionalidad existente

¿Deseas que implemente la solución con la nueva tabla y los endpoints actualizados?
