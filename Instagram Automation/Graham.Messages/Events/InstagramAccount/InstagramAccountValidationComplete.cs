using NServiceBus;

namespace Graham.Messages.Events.InstagramAccount
{
    public class InstagramAccountValidationComplete : IEvent
    {
        public long InstagramAccountId { get; set; }
        public bool ValidationResult { get; set; }
    }
}
