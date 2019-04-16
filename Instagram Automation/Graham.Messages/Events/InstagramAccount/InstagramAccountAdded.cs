using NServiceBus;

namespace Graham.Messages.Events.InstagramAccount
{
    public class InstagramAccountAdded : IEvent
    {
        public long InstagramAccountId { get; set; }
        public string Username { get; set; }
        public string Password { get; set; }
    }
}
