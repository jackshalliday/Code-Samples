using NServiceBus;
using System;
using System.Collections.Generic;
using System.Text;

namespace Graham.Backend.Sagas.InstagramAccount
{
    public class InstagramAccountValidationSagaData : ContainSagaData
    {
        public virtual long InstagramAccountId { get; set; }
        public virtual bool InstagramAccountValidationResult { get; set; }
    }
}
