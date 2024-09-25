<?php

namespace Larapay\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LarapayTransaction extends Model
{
  /**
   * Get the parent transactionable model.
   */
  public function transactionable(): MorphTo
  {
      return $this->morphTo();
  }

  /**
   * Get the parent transactionable model.
   */
  public function transaction(): BelongsTo
  {
    return $this->belongsTo(LarapayTransaction::class, 'parent_id');
  }
}
